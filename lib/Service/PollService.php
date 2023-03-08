<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Service;

use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Event\PollArchivedEvent;
use OCA\Polls\Event\PollCreatedEvent;
use OCA\Polls\Event\PollDeletedEvent;
use OCA\Polls\Event\PollOwnerChangeEvent;
use OCA\Polls\Event\PollRestoredEvent;
use OCA\Polls\Event\PollTakeoverEvent;
use OCA\Polls\Event\PollUpdatedEvent;
use OCA\Polls\Exceptions\EmptyTitleException;
use OCA\Polls\Exceptions\InvalidAccessException;
use OCA\Polls\Exceptions\InvalidShowResultsException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Model\Acl;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Search\ISearchQuery;

class PollService {
	private string $userId;

	public function __construct(
		private Acl $acl,
		private AppSettings $appSettings,
		private IEventDispatcher $eventDispatcher,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private MailService $mailService,
		private Poll $poll,
		private PollMapper $pollMapper,
		private VoteMapper $voteMapper
	) {
		$this->userId = $this->userSession->getUser()?->getUID() ?? '';
	}

	/**
	 * Get list of polls
	 */
	public function list(): array {
		$pollList = [];
		try {
			$polls = $this->pollMapper->findForMe($this->userId);

			foreach ($polls as $poll) {
				try {
					$this->acl->setPollId($poll->getId());
					// TODO: Not the elegant way. Improvement neccessary
					$pollList[] = (object) array_merge(
						(array) json_decode(json_encode($poll)),
						(array) json_decode(json_encode($this->acl))
					);
				} catch (NotAuthorizedException $e) {
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
	 */
	public function search(ISearchQuery $query): array {
		$pollList = [];
		try {
			$polls = $this->pollMapper->search($this->userId, $query);

			foreach ($polls as $poll) {
				try {
					$this->acl->setPollId($poll->getId());
					// TODO: Not the elegant way. Improvement neccessary
					$pollList[] = $poll;
				} catch (NotAuthorizedException $e) {
					continue;
				}
			}
		} catch (DoesNotExistException $e) {
			// silent catch
		}
		return $pollList;
	}

	/**
	 *   * Get list of polls
	 *
	 * @return Poll[]
	 */
	public function listForAdmin(): array {
		$pollList = [];
		$userId = $this->userId;
		if ($this->groupManager->isAdmin($userId)) {
			try {
				$pollList = $this->pollMapper->findForAdmin($userId);
			} catch (DoesNotExistException $e) {
				// silent catch
			}
		}
		return $pollList;
	}

	/**
	 * Update poll configuration
	 *
	 * @return Poll
	 */
	public function takeover(int $pollId): Poll {
		$this->poll = $this->pollMapper->find($pollId);

		$this->eventDispatcher->dispatchTyped(new PollTakeOverEvent($this->poll));

		$this->poll->setOwner($this->userId);
		$this->pollMapper->update($this->poll);

		return $this->poll;
	}

	/**
	 * @return Poll[]
	 *
	 * @psalm-return array<Poll>
	 */
	public function transferPolls(string $sourceUser, string $targetUser): array {
		if ($this->userManager->get($targetUser) instanceof IUser) {
			$pollsToTransfer = $this->pollMapper->findOwner($sourceUser);
			foreach ($pollsToTransfer as $poll) {
				$poll->setOwner($targetUser);
				$this->pollMapper->update($poll);
				$this->eventDispatcher->dispatchTyped(new PollOwnerChangeEvent($poll));
			}
			return $pollsToTransfer;
		}
		throw new InvalidUsernameException('The user id "' . $targetUser . '" is not valid.');
	}


	/**
	 * get poll configuration
	 *
	 * @return Poll
	 *
	 * @psalm-return Poll
	 */
	public function get(int $pollId) {
		$this->acl->setPollId($pollId);
		$this->poll = $this->pollMapper->find($pollId);

		if (!$this->acl->getIsLoggedIn() && $this->poll->getUserId() !== $this->acl->getUserId()) {
			$this->poll->generateHashedUserId();
		}

		return $this->poll;
	}

	/**
	 * Add poll
	 */
	public function add(string $type, string $title): Poll {
		if (!$this->appSettings->getPollCreationAllowed()) {
			throw new NotAuthorizedException;
		}

		// Validate valuess
		if (!in_array($type, $this->getValidPollType())) {
			throw new InvalidPollTypeException('Invalid poll type');
		}

		if (!$title) {
			throw new EmptyTitleException('Title must not be empty');
		}

		$this->poll = new Poll();
		$this->poll->setType($type);
		$this->poll->setCreated(time());
		$this->poll->setOwner($this->userId);
		$this->poll->setTitle($title);
		$this->poll->setDescription('');
		$this->poll->setAccess(Poll::ACCESS_PRIVATE);
		$this->poll->setExpire(0);
		$this->poll->setAnonymous(0);
		$this->poll->setAllowMaybe(0);
		$this->poll->setVoteLimit(0);
		$this->poll->setShowResults(Poll::SHOW_RESULTS_ALWAYS);
		$this->poll->setDeleted(0);
		$this->poll->setAdminAccess(0);
		$this->poll->setImportant(0);
		$this->poll = $this->pollMapper->insert($this->poll);

		$this->eventDispatcher->dispatchTyped(new PollCreatedEvent($this->poll));

		return $this->poll;
	}

	/**
	 * Update poll configuration
	 *
	 * @return Poll
	 */
	public function update(int $pollId, array $poll): Poll {
		// $this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);
		// $this->poll = $this->acl->getPoll();
		$this->poll = $this->pollMapper->find($pollId);

		// Validate valuess
		if (isset($poll['showResults']) && !in_array($poll['showResults'], $this->getValidShowResults())) {
			throw new InvalidShowResultsException('Invalid value for prop showResults');
		}

		if (isset($poll['title']) && !$poll['title']) {
			throw new EmptyTitleException('Title must not be empty');
		}

		if (isset($poll['access']) && !in_array($poll['access'], $this->getValidAccess())) {
			if (!in_array($poll['access'], $this->getValidAccess())) {
				throw new InvalidAccessException('Invalid value for prop access ' . $poll['access']);
			}

			if ($poll['access'] === (Poll::ACCESS_OPEN)) {
				$this->acl->setPollId($pollId, Acl::PERMISSION_ALL_ACCESS);
			}
		}

		// Set the expiry time to the actual servertime to avoid an
		// expiry misinterpration when using acl
		if (isset($poll['expire']) && $poll['expire'] < 0) {
			$poll['expire'] = time();
		}

		$this->poll->deserializeArray($poll);
		$this->pollMapper->update($this->poll);
		$this->eventDispatcher->dispatchTyped(new PollUpdatedEvent($this->poll));

		return $this->poll;
	}


	/**
	 * Move to archive or restore
	 *
	 * @return Poll
	 */
	public function toggleArchive(int $pollId): Poll {
		$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_DELETE);
		$this->poll = $this->acl->getPoll();

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
	 *
	 * @return Poll
	 */
	public function delete(int $pollId): Poll {
		$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_DELETE);
		$this->poll = $this->acl->getPoll();

		$this->eventDispatcher->dispatchTyped(new PollDeletedEvent($this->poll));

		$this->pollMapper->delete($this->poll);

		return $this->poll;
	}

	/**
	 * Clone poll
	 *
	 * @return Poll
	 */
	public function clone(int $pollId): Poll {
		$this->acl->setPollId($pollId);
		$origin = $this->acl->getPoll();

		$this->poll = new Poll();
		$this->poll->setCreated(time());
		$this->poll->setOwner($this->userId);
		$this->poll->setTitle('Clone of ' . $origin->getTitle());
		$this->poll->setDeleted(0);
		$this->poll->setAccess(Poll::ACCESS_PRIVATE);

		$this->poll->setType($origin->getType());
		$this->poll->setDescription($origin->getDescription());
		$this->poll->setExpire($origin->getExpire());
		$this->poll->setAnonymous($origin->getAnonymous());
		$this->poll->setAllowMaybe($origin->getAllowMaybe());
		$this->poll->setVoteLimit($origin->getVoteLimit());
		$this->poll->setShowResults($origin->getShowResults());
		$this->poll->setAdminAccess($origin->getAdminAccess());
		$this->poll->setImportant($origin->getImportant());

		$this->poll = $this->pollMapper->insert($this->poll);
		$this->eventDispatcher->dispatchTyped(new PollCreatedEvent($this->poll));
		return $this->poll;
	}

	/**
	 * Collect email addresses from particitipants
	 *
	 * @return string[][]
	 *
	 * @psalm-return list<array{displayName: string, emailAddress: string, combined: string}>
	 */
	public function getParticipantsEmailAddresses(int $pollId): array {
		$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);
		$this->poll = $this->acl->getPoll();

		$votes = $this->voteMapper->findParticipantsByPoll($this->poll->getId());
		$list = [];
		foreach ($votes as $vote) {
			$list[] = [
				'displayName' => $vote->getDisplayName(),
				'emailAddress' => $this->mailService->resolveEmailAddress($this->poll->getId(), $vote->getUserId()),
				'combined' => $vote->getDisplayName() . ' <' . $this->mailService->resolveEmailAddress($this->poll->getId(), $vote->getUserId()) . '>'];
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
