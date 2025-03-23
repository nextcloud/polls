<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Event\PollArchivedEvent;
use OCA\Polls\Event\PollCloseEvent;
use OCA\Polls\Event\PollCreatedEvent;
use OCA\Polls\Event\PollDeletedEvent;
use OCA\Polls\Event\PollOwnerChangeEvent;
use OCA\Polls\Event\PollReopenEvent;
use OCA\Polls\Event\PollRestoredEvent;
use OCA\Polls\Event\PollTakeoverEvent;
use OCA\Polls\Event\PollUpdatedEvent;
use OCA\Polls\Exceptions\EmptyTitleException;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\InvalidAccessException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Exceptions\InvalidShowResultsException;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Exceptions\UserNotFoundException;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Model\UserBase;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Search\ISearchQuery;

class PollService {

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private AppSettings $appSettings,
		private IEventDispatcher $eventDispatcher,
		private Poll $poll,
		private PollMapper $pollMapper,
		private UserMapper $userMapper,
		private UserSession $userSession,
		private VoteMapper $voteMapper,
	) {
	}

	/**
	 * Get list of polls including Threshold for "relevant polls"
	 */
	public function list(): array {
		$pollList = $this->pollMapper->findForMe($this->userSession->getCurrentUserId());
		if ($this->userSession->getCurrentUser()->getIsAdmin()) {
			return $pollList;
		}

		return array_values(array_filter($pollList, function (Poll $poll): bool {
			return $poll->getIsAllowed(Poll::PERMISSION_POLL_VIEW);
		}));
	}

	/**
	 * Get list of polls
	 */
	public function search(ISearchQuery $query): array {
		$pollList = [];
		try {
			$polls = $this->pollMapper->search($query);

			foreach ($polls as $poll) {
				try {
					$poll->request(Poll::PERMISSION_POLL_VIEW);
					$pollList[] = $poll;
				} catch (ForbiddenException $e) {
					continue;
				}
			}
		} catch (DoesNotExistException $e) {
			// silent catch
		}
		return $pollList;
	}

	/**
	 * Get list of polls
	 * @return Poll[]
	 */
	public function listForAdmin(): array {
		$pollList = [];
		if ($this->userSession->getCurrentUser()->getIsAdmin()) {
			try {
				$pollList = $this->pollMapper->findForAdmin($this->userSession->getCurrentUserId());
			} catch (DoesNotExistException $e) {
				// silent catch
			}
		}
		return $pollList;
	}

	/**
	 * Update poll configuration
	 * @return Poll
	 */
	public function takeover(int $pollId, ?UserBase $targetUser = null): Poll {
		if (!$targetUser) {
			$targetUser = $this->userSession->getCurrentUser();
		}

		$this->poll = $this->pollMapper->find($pollId);

		$this->eventDispatcher->dispatchTyped(new PollTakeOverEvent($this->poll));

		$this->poll->setOwner($targetUser->getId());
		$this->pollMapper->update($this->poll);

		return $this->poll;
	}

	/**
	 * @return Poll[]
	 * @psalm-return array<Poll>
	 */
	public function transferPolls(string $sourceUser, string $targetUser): array {
		try {
			$this->userMapper->getUserFromUserBase($targetUser);
		} catch (UserNotFoundException $e) {
			throw new InvalidUsernameException('The user id "' . $targetUser . '" for the target user is not valid.');
		}

		$pollsToTransfer = $this->pollMapper->listByOwner($sourceUser);

		foreach ($pollsToTransfer as &$poll) {
			$poll = $this->executeTransfer($poll, $targetUser);
		}
		return $pollsToTransfer;
	}

	/**
	 * @return Poll
	 */
	public function transferPoll(int $pollId, string $targetUser): Poll {
		try {
			$this->userMapper->getUserFromUserBase($targetUser);
		} catch (UserNotFoundException $e) {
			throw new InvalidUsernameException('The user id "' . $targetUser . '" for the target user is not valid.');
		}

		return $this->executeTransfer($this->pollMapper->find($pollId), $targetUser);
	}

	private function executeTransfer(Poll $poll, string $targetUser): Poll {
		$poll->setOwner($targetUser);
		$this->pollMapper->update($poll);
		$this->eventDispatcher->dispatchTyped(new PollOwnerChangeEvent($poll));
		return $poll;

	}
	/**
	 * get poll configuration
	 * @return Poll
	 */
	public function get(int $pollId) {
		try {
			$this->poll = $this->pollMapper->find($pollId);
			$this->poll->request(Poll::PERMISSION_POLL_VIEW);
			return $this->poll;
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Poll not found');
		}
	}

	/**
	 * Add poll
	 */
	public function add(string $type, string $title): Poll {
		if (!$this->appSettings->getPollCreationAllowed()) {
			throw new ForbiddenException('Poll creation is disabled');
		}

		// Validate valuess
		if (!in_array($type, $this->getValidPollType())) {
			throw new InvalidPollTypeException('Invalid poll type');
		}

		if (!$title) {
			throw new EmptyTitleException('Title must not be empty');
		}

		$timestamp = time();
		$this->poll = new Poll();
		$this->poll->setType($type);
		$this->poll->setTitle($title);
		$this->poll->setCreated($timestamp);
		$this->poll->setLastInteraction($timestamp);
		$this->poll->setOwner($this->userSession->getCurrentUserId());

		// create new poll before resetting all values to
		// ensure that the poll has all required values and an id
		// latter checks mai fail if the poll has no id
		$this->poll = $this->pollMapper->insert($this->poll);

		$this->poll->setDescription('');
		$this->poll->setAccess(Poll::ACCESS_PRIVATE);
		$this->poll->setExpire(0);
		$this->poll->setAnonymousSafe(0);
		$this->poll->setAllowMaybe(0);
		$this->poll->setVoteLimit(0);
		$this->poll->setShowResults(Poll::SHOW_RESULTS_ALWAYS);
		$this->poll->setDeleted(0);
		$this->poll->setAdminAccess(0);

		$this->pollMapper->update($this->poll);

		$this->eventDispatcher->dispatchTyped(new PollCreatedEvent($this->poll));

		return $this->poll;
	}

	/**
	 * Update poll configuration
	 * @return Poll
	 */
	public function update(int $pollId, array $pollConfiguration): Poll {
		$this->poll = $this->pollMapper->find($pollId);
		$this->poll->request(Poll::PERMISSION_POLL_EDIT);

		// Validate valuess
		if (isset($pollConfiguration['showResults']) && !in_array($pollConfiguration['showResults'], $this->getValidShowResults())) {
			throw new InvalidShowResultsException('Invalid value for prop showResults');
		}

		if (isset($pollConfiguration['title']) && !$pollConfiguration['title']) {
			throw new EmptyTitleException('Title must not be empty');
		}

		if (isset($pollConfiguration['anonymous'])
			&& $pollConfiguration['anonymous'] === 0
			&& $this->poll->getAnonymous() < 0
		) {
			throw new ForbiddenException('Deanonimization is not allowed');
		}

		if (isset($pollConfiguration['access']) && !in_array($pollConfiguration['access'], $this->getValidAccess())) {
			if (!in_array($pollConfiguration['access'], $this->getValidAccess())) {
				throw new InvalidAccessException('Invalid value for prop access ' . $pollConfiguration['access']);
			}

			if ($pollConfiguration['access'] === (Poll::ACCESS_OPEN)) {
				$this->appSettings->getAllAccessAllowed();
			}
		}

		// Set the expiry time to the actual servertime to avoid an
		// expiry misinterpration when using permission checks
		if (isset($pollConfiguration['expire']) && $pollConfiguration['expire'] < 0) {
			$pollConfiguration['expire'] = time();
		}

		$this->poll->deserializeArray($pollConfiguration);
		$this->poll = $this->pollMapper->update($this->poll);

		$this->eventDispatcher->dispatchTyped(new PollUpdatedEvent($this->poll));

		return $this->poll;
	}

	/**
	 * Manually lock anonymization
	 * @return Poll
	 */
	public function lockAnonymous(int $pollId): Poll {
		$this->poll = $this->pollMapper->find($pollId);

		// Only possible, if poll is already anonymized
		if ($this->poll->getAnonymous() < 1) {
			throw new ForbiddenException('Anonymization is not allowed');
		}

		// Only possible, if user is allowed to deanonymize
		$this->poll->request(Poll::PERMISSION_DEANONYMIZE);

		$this->poll->setAnonymous(-1);
		$this->poll = $this->pollMapper->update($this->poll);

		$this->eventDispatcher->dispatchTyped(new PollUpdatedEvent($this->poll));

		return $this->poll;
	}

	/**
	 * Update timestamp for last interaction with polls
	 */
	public function setLastInteraction(int $pollId): void {
		if ($pollId) {
			$this->pollMapper->setLastInteraction($pollId);
		}
	}


	/**
	 * Move to archive or restore
	 * @return Poll
	 */
	public function toggleArchive(int $pollId): Poll {
		$this->poll = $this->pollMapper->find($pollId);
		$this->poll->request(Poll::PERMISSION_POLL_DELETE);

		$this->poll->setDeleted($this->poll->getDeleted() ? 0 : time());
		$this->poll = $this->pollMapper->update($this->poll);

		if ($this->poll->getDeleted()) {
			$this->eventDispatcher->dispatchTyped(new PollArchivedEvent($this->poll));
		} else {
			$this->eventDispatcher->dispatchTyped(new PollRestoredEvent($this->poll));
		}

		return $this->poll;
	}

	/**
	 * Delete poll
	 * @return Poll
	 */
	public function delete(int $pollId): Poll {
		$this->poll = $this->pollMapper->find($pollId);
		$this->poll->request(Poll::PERMISSION_POLL_DELETE);

		$this->eventDispatcher->dispatchTyped(new PollDeletedEvent($this->poll));

		$this->pollMapper->delete($this->poll);

		return $this->poll;
	}

	/**
	 * Close poll
	 * @return Poll
	 */
	public function close(int $pollId): Poll {
		$this->pollMapper->find($pollId)->request(Poll::PERMISSION_POLL_EDIT);
		return $this->toggleClose($pollId, time() - 5);
	}

	/**
	 * Reopen poll
	 * @return Poll
	 */
	public function reopen(int $pollId): Poll {
		$this->pollMapper->find($pollId)->request(Poll::PERMISSION_POLL_EDIT);
		return $this->toggleClose($pollId, 0);
	}

	/**
	 * Close poll
	 * @return Poll
	 */
	private function toggleClose(int $pollId, int $expiry): Poll {
		$this->poll = $this->pollMapper->find($pollId);
		$this->poll->request(Poll::PERMISSION_POLL_EDIT);

		$this->poll->setExpire($expiry);
		if ($expiry > 0) {
			$this->eventDispatcher->dispatchTyped(new PollCloseEvent($this->poll));
		} else {
			$this->eventDispatcher->dispatchTyped(new PollReopenEvent($this->poll));
		}

		$this->poll = $this->pollMapper->update($this->poll);

		return $this->poll;
	}

	/**
	 * Clone poll
	 * @return Poll
	 */
	public function clone(int $pollId): Poll {
		$origin = $this->pollMapper->find($pollId);
		$origin->request(Poll::PERMISSION_POLL_VIEW);
		$this->appSettings->getPollCreationAllowed();

		$this->poll = new Poll();
		$this->poll->setCreated(time());
		$this->poll->setOwner($this->userSession->getCurrentUserId());
		$this->poll->setTitle('Clone of ' . $origin->getTitle());
		$this->poll->setDeleted(0);
		$this->poll->setAccess(Poll::ACCESS_PRIVATE);

		$this->poll->setType($origin->getType());
		$this->poll->setDescription($origin->getDescription());
		$this->poll->setExpire($origin->getExpire());
		// deanonymize cloned polls by default, to avoid locked anonymous polls
		$this->poll->setAnonymous(0);
		$this->poll->setAllowMaybe($origin->getAllowMaybe());
		$this->poll->setVoteLimit($origin->getVoteLimit());
		$this->poll->setShowResults($origin->getShowResults());
		$this->poll->setAdminAccess($origin->getAdminAccess());

		$this->poll = $this->pollMapper->insert($this->poll);
		$this->eventDispatcher->dispatchTyped(new PollCreatedEvent($this->poll));
		return $this->poll;
	}

	/**
	 * Collect email addresses from particitipants
	 *
	 */
	public function getParticipantsEmailAddresses(int $pollId): array {
		$this->poll = $this->pollMapper->find($pollId);
		$this->poll->request(Poll::PERMISSION_POLL_EDIT);

		$votes = $this->voteMapper->findParticipantsByPoll($this->poll->getId());
		$list = [];
		foreach ($votes as $vote) {
			$user = $vote->getUser();
			$list[] = [
				'displayName' => $user->getDisplayName(),
				'emailAddress' => $user->getEmailAddress(),
				'combined' => $user->getEmailAndDisplayName(),
			];
		}
		return $list;
	}

	/**
	 * Get valid values for configuration options
	 *
	 * @return array
	 *
	 * @psalm-return array{pollType: mixed, access: mixed, showResults: mixed}
	 */
	public function getValidEnum(): array {
		return [
			'pollType' => $this->getValidPollType(),
			'access' => $this->getValidAccess(),
			'showResults' => $this->getValidShowResults()
		];
	}

	/**
	 * Get valid values for pollType
	 *
	 * @return string[]
	 *
	 * @psalm-return array{0: string, 1: string}
	 */
	private function getValidPollType(): array {
		return [Poll::TYPE_DATE, Poll::TYPE_TEXT];
	}

	/**
	 * Get valid values for access
	 *
	 * @return string[]
	 *
	 * @psalm-return array{0: string, 1: string}
	 */
	private function getValidAccess(): array {
		return [Poll::ACCESS_PRIVATE, Poll::ACCESS_OPEN];
	}

	/**
	 * Get valid values for showResult
	 *
	 * @return string[]
	 *
	 * @psalm-return array{0: string, 1: string, 2: string}
	 */
	private function getValidShowResults(): array {
		return [Poll::SHOW_RESULTS_ALWAYS, Poll::SHOW_RESULTS_CLOSED, Poll::SHOW_RESULTS_NEVER];
	}
}
