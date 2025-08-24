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
use OCA\Polls\Event\PollUpdatedEvent;
use OCA\Polls\Exceptions\AlreadyDeletedException;
use OCA\Polls\Exceptions\EmptyTitleException;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\InvalidAccessException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Exceptions\InvalidShowResultsException;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\InvalidVotingVariantException;
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
	public function listPolls(): array {
		$pollList = $this->pollMapper->findForMe($this->userSession->getCurrentUserId());
		if ($this->userSession->getCurrentUser()->getIsAdmin()) {
			return $pollList;
		}

		return array_values(array_filter($pollList, function (Poll $poll): bool {
			return $poll->getIsAllowed(Poll::PERMISSION_POLL_ACCESS);
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
					$poll->request(Poll::PERMISSION_POLL_ACCESS);
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
	 * @return Poll[]
	 * @psalm-return array<Poll>
	 */
	public function transferPolls(string $sourceUserId, string $targetUserId): array {
		try {
			$targetUser = $this->userMapper->getUserFromUserBase($targetUserId);
		} catch (UserNotFoundException $e) {
			throw new InvalidUsernameException('The user id "' . $targetUserId . '" for the target user is not valid.');
		}

		$pollsToTransfer = $this->pollMapper->listByOwner($sourceUserId);

		foreach ($pollsToTransfer as &$poll) {
			$poll = $this->transferPoll($poll, $targetUser);
		}
		return $pollsToTransfer;
	}

	/**
	 * Update poll configuration
	 * @return Poll
	 */
	public function takeover(int $pollId, ?UserBase $targetUser = null): Poll {
		if ($targetUser === null) {
			$targetUser = $this->userSession->getCurrentUser();
		}
		return $this->transferPoll($pollId, $targetUser);
	}

	/**
	 * Transfer ownership of a poll
	 * @param int|Poll $poll poll or pollId of poll to transfer ownership
	 * @param string|UserBase $targetUser User to transfer polls to. If null the current user will be used
	 */
	public function transferPoll(int|Poll $poll, string|UserBase $targetUser): Poll {
		if (!($poll instanceof Poll)) {
			$poll = $this->pollMapper->get($poll);
		}

		$poll->request(Poll::PERMISSION_POLL_CHANGE_OWNER);

		if (!($targetUser instanceof UserBase)) {
			$userId = $targetUser;
			try {
				$targetUser = $this->userMapper->getUserFromUserBase($userId);
			} catch (UserNotFoundException $e) {
				// to keep psalm quiet
				throw new InvalidUsernameException('The user id "' . $userId . '" for the target user is not valid.');
			}
		}

		$oldOwner = $poll->getOwner();

		$poll->setOwner($targetUser->getId());
		$poll = $this->pollMapper->update($poll);

		$this->eventDispatcher->dispatchTyped(new PollOwnerChangeEvent($poll, $oldOwner, $poll->getOwner()));

		return $poll;
	}

	/**
	 * get poll configuration
	 * @return Poll
	 */
	public function get(int $pollId) {
		try {
			$this->poll = $this->pollMapper->get($pollId);
			$this->poll->request(Poll::PERMISSION_POLL_ACCESS);
			return $this->poll;
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Poll not found');
		}
	}

	public function getPollOwnerFromDB(int $pollId): UserBase {
		try {
			$poll = $this->pollMapper->get($pollId);
			return $poll->getUser();
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Poll not found');
		}
	}
	/**
	 * Add poll
	 */
	public function add(string $type, string $title, string $votingVariant = Poll::VARIANT_SIMPLE): Poll {
		if (!$this->appSettings->getPollCreationAllowed()) {
			throw new ForbiddenException('Poll creation is disabled');
		}

		// Validate values
		if (!in_array($type, $this->getValidPollType())) {
			throw new InvalidPollTypeException('Invalid poll type');
		}

		if (!in_array($votingVariant, $this->getValidVotingVariant())) {
			throw new InvalidVotingVariantException('Invalid voting variant');
		}


		if (!$title) {
			throw new EmptyTitleException('Title must not be empty');
		}

		$timestamp = time();
		$this->poll = new Poll();
		$this->poll->setType($type);
		$this->poll->setVotingVariant($votingVariant);
		$this->poll->setTitle($title);
		$this->poll->setCreated($timestamp);
		$this->poll->setLastInteraction($timestamp);
		$this->poll->setOwner($this->userSession->getCurrentUserId());

		// create new poll before resetting all values to
		// ensure that the poll has all required values and an id
		// later checks may fail if the poll has no id
		$this->poll = $this->pollMapper->insert($this->poll);

		$this->poll->setDescription('');
		$this->poll->setAccess(Poll::ACCESS_PRIVATE);
		$this->poll->setExpire(0);
		$this->poll->setAnonymousSafe(0);
		$this->poll->setAllowMaybe(0);
		$this->poll->setChosenRank('');
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
	 *
	 * @param int $pollId Poll id
	 * @param array $pollConfiguration Poll configuration
	 * @return array
	 *
	 * @psalm-return array{poll: Poll, diff: array, changes: array}
	 */
	public function update(int $pollId, array $pollConfiguration): array {
		$this->poll = $this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_EDIT);

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

		if (isset($pollConfiguration['access'])) {
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

		$diff = new DiffService($this->poll);

		$this->poll->deserializeArray($pollConfiguration);
		$this->poll = $this->pollMapper->update($this->poll);
		$this->eventDispatcher->dispatchTyped(new PollUpdatedEvent($this->poll));

		$diff->setComparisonObject($this->poll);

		return [
			'poll' => $this->poll,
			'diff' => $diff->getFullDiff(),
			'changes' => $diff->getNewValuesDiff(),
		];
	}

	/**
	 * Manually lock anonymization
	 * @return Poll
	 */
	public function lockAnonymous(int $pollId): Poll {
		$this->poll = $this->pollMapper->get($pollId);

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
		$this->poll = $this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_DELETE);

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
		try {
			$this->poll = $this->pollMapper->get($pollId)
				->request(Poll::PERMISSION_POLL_DELETE);
		} catch (DoesNotExistException $e) {
			throw new AlreadyDeletedException('Poll not found, assume already deleted');
		}

		$this->eventDispatcher->dispatchTyped(new PollDeletedEvent($this->poll));

		$this->pollMapper->delete($this->poll);
		return $this->poll;
	}

	/**
	 * Close poll
	 * @return Poll
	 */
	public function close(int $pollId): Poll {
		$this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_EDIT);
		return $this->toggleClose($pollId, time() - 5);
	}

	/**
	 * Reopen poll
	 * @return Poll
	 */
	public function reopen(int $pollId): Poll {
		$this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_EDIT);
		return $this->toggleClose($pollId, 0);
	}

	/**
	 * Close poll
	 * @return Poll
	 */
	private function toggleClose(int $pollId, int $expiry): Poll {
		$this->poll = $this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_EDIT);

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
		$origin = $this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_ACCESS);
		$this->appSettings->getPollCreationAllowed();

		$this->poll = new Poll();
		$this->poll->setCreated(time());
		$this->poll->setOwner($this->userSession->getCurrentUserId());
		$this->poll->setTitle('Clone of ' . $origin->getTitle());
		$this->poll->setDeleted(0);
		$this->poll->setAccess(Poll::ACCESS_PRIVATE);

		$this->poll->setType($origin->getType());
		$this->poll->setVotingVariant($origin->getVotingVariant());
		$this->poll->setDescription($origin->getDescription());
		$this->poll->setExpire($origin->getExpire());
		// deanonymize cloned polls by default, to avoid locked anonymous polls
		$this->poll->setAnonymous(0);
		$this->poll->setAllowMaybe($origin->getAllowMaybe());
		$this->poll->setChosenRank($origin->getChosenRank());
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
		$this->poll = $this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_EDIT);

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
	 * Get valid values for votingVariant
	 *
	 * @return string[]
	 *
	 * @psalm-return array{0: string, 1: string}
	 */
	private function getValidVotingVariant(): array {
		return [Poll::VARIANT_SIMPLE, Poll::VARIANT_GENERIC];
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
