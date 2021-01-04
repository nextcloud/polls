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

use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\EmptyTitleException;
use OCA\Polls\Exceptions\InvalidAccessException;
use OCA\Polls\Exceptions\InvalidShowResultsException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Exceptions\NotAuthorizedException;


use OCA\Polls\Db\Log;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Model\Acl;

class PollService {

	/** @var string */
	private $userId;

	/** @var PollMapper */
	private $pollMapper;

	/** @var Poll */
	private $poll;

	/** @var VoteMapper */
	private $voteMapper;

	/** @var Vote */
	private $vote;

	/** @var LogService */
	private $logService;

	/** @var NotificationService */
	private $notificationService;

	/** @var MailService */
	private $mailService;

	/** @var Acl */
	private $acl;

	public function __construct(
		?string $UserId,
		PollMapper $pollMapper,
		Poll $poll,
		VoteMapper $voteMapper,
		Vote $vote,
		LogService $logService,
		NotificationService $notificationService,
		MailService $mailService,
		Acl $acl
	) {
		$this->pollMapper = $pollMapper;
		$this->userId = $UserId;
		$this->poll = $poll;
		$this->voteMapper = $voteMapper;
		$this->vote = $vote;
		$this->logService = $logService;
		$this->notificationService = $notificationService;
		$this->mailService = $mailService;
		$this->acl = $acl;
	}

	/**
	 * Get list of polls
	 */
	public function list(): array {
		$pollList = [];
		try {
			$polls = $this->pollMapper->findForMe(\OC::$server->getUserSession()->getUser()->getUID());

			foreach ($polls as $poll) {
				try {
					$this->acl->setPoll($poll)->requestView();
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
	 *   * Get list of polls
	 *
	 * @return Poll[]
	 */
	public function listForAdmin(): array {
		$pollList = [];
		$userId = \OC::$server->getUserSession()->getUser()->getUID();
		if (\OC::$server->getGroupManager()->isAdmin($userId)) {
			try {
				$pollList = $this->pollMapper->findForAdmin($userId);
			} catch (DoesNotExistException $e) {
				// silent catch
			}
		}
		return $pollList;
	}

	/**
	 * 	 * Update poll configuration
	 *
	 * @return Poll
	 */
	public function takeover(int $pollId): Poll {
		$this->poll = $this->pollMapper->find($pollId);
		$originalOwner = $this->poll->getOwner();
		$this->poll->setOwner(\OC::$server->getUserSession()->getUser()->getUID());

		$this->pollMapper->update($this->poll);
		$this->logService->setLog($this->poll->getId(), Log::MSG_ID_OWNERCHANGE);

		// send notification to the original owner
		$this->notificationService->createNotification([
			'msgId' => 'takeOverPoll',
			'objectType' => 'poll',
			'objectValue' => $this->poll->getId(),
			'recipient' => $originalOwner,
			'actor' => $this->userId
		]);

		return $this->poll;
	}

	/**
	 * 	 * get poll configuration
	 *
	 * @return Poll
	 */
	public function get(int $pollId): Poll {
		$this->poll = $this->pollMapper->find($pollId);
		$this->acl->setPoll($this->poll)->requestView();
		return $this->poll;
	}

	/**
	 * Add poll
	 */
	public function add(string $type, string $title) {
		if (!\OC::$server->getUserSession()->isLoggedIn()) {
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
		$this->poll->setOwner(\OC::$server->getUserSession()->getUser()->getUID());
		$this->poll->setTitle($title);
		$this->poll->setDescription('');
		$this->poll->setAccess(Poll::ACCESS_HIDDEN);
		$this->poll->setExpire(0);
		$this->poll->setAnonymous(0);
		$this->poll->setFullAnonymous(0);
		$this->poll->setAllowMaybe(0);
		$this->poll->setVoteLimit(0);
		$this->poll->setSettings('');
		$this->poll->setOptions('');
		$this->poll->setShowResults(Poll::SHOW_RESULTS_ALWAYS);
		$this->poll->setDeleted(0);
		$this->poll->setAdminAccess(0);
		$this->poll->setImportant(0);
		$this->poll = $this->pollMapper->insert($this->poll);

		$this->logService->setLog($this->poll->getId(), Log::MSG_ID_ADDPOLL);

		return $this->poll;
	}

	/**
	 * 	 * Update poll configuration
	 *
	 * @return Poll
	 */
	public function update(int $pollId, array $poll): Poll {
		$this->poll = $this->pollMapper->find($pollId);
		$this->acl->setPoll($this->poll)->requestEdit();

		// Validate valuess
		if (isset($poll['showResults']) && !in_array($poll['showResults'], $this->getValidShowResults())) {
			throw new InvalidShowResultsException('Invalid value for prop showResults');
		}
		if (isset($poll['access']) && !in_array($poll['access'], $this->getValidAccess())) {
			throw new InvalidAccessException('Invalid value for prop access ' . $poll['access']);
		}

		if (isset($poll['title']) && !$poll['title']) {
			throw new EmptyTitleException('Title must not be empty');
		}
		$this->poll->deserializeArray($poll);

		$this->pollMapper->update($this->poll);
		$this->logService->setLog($this->poll->getId(), Log::MSG_ID_UPDATEPOLL);

		return $this->poll;
	}


	/**
	 * 	 * Switch deleted status (move to deleted polls)
	 *
	 * @return Poll
	 */
	public function switchDeleted(int $pollId): Poll {
		$this->poll = $this->pollMapper->find($pollId);
		$this->acl->setPoll($this->poll)->requestDelete();

		if ($this->poll->getDeleted()) {
			$this->poll->setDeleted(0);
		} else {
			$this->poll->setDeleted(time());
		}

		$this->poll = $this->pollMapper->update($this->poll);
		$this->logService->setLog($this->poll->getId(), Log::MSG_ID_DELETEPOLL);

		if ($this->userId !== $this->poll->getOwner()) {
			// send notification to the original owner
			$this->notificationService->createNotification([
				'msgId' => 'softDeletePollByOther',
				'objectType' => 'poll',
				'objectValue' => $this->poll->getId(),
				'recipient' => $this->poll->getOwner(),
				'actor' => $this->userId,
				'pollTitle' => $this->poll->getTitle()
			]);
		}

		return $this->poll;
	}

	/**
	 * 	 * Delete poll
	 *
	 * @return Poll
	 */
	public function delete(int $pollId): Poll {
		$this->poll = $this->pollMapper->find($pollId);
		$this->acl->setPoll($this->poll)->requestDelete();

		$this->pollMapper->delete($this->poll);

		if ($this->userId !== $this->poll->getOwner()) {
			// send notification to the original owner
			$this->notificationService->createNotification([
				'msgId' => 'deletePollByOther',
				'objectType' => 'poll',
				'objectValue' => $this->poll->getId(),
				'recipient' => $this->poll->getOwner(),
				'actor' => $this->userId,
				'pollTitle' => $this->poll->getTitle()
			]);
		}
		return $this->poll;
	}

	/**
	 * 	 * Clone poll
	 *
	 * @return Poll
	 */
	public function clone(int $pollId): Poll {
		$origin = $this->pollMapper->find($pollId);
		$this->acl->setPoll($origin)->requestView();

		$this->poll = new Poll();
		$this->poll->setCreated(time());
		$this->poll->setOwner(\OC::$server->getUserSession()->getUser()->getUID());
		$this->poll->setTitle('Clone of ' . $origin->getTitle());
		$this->poll->setDeleted(0);
		$this->poll->setAccess(Poll::ACCESS_HIDDEN);

		$this->poll->setType($origin->getType());
		$this->poll->setDescription($origin->getDescription());
		$this->poll->setExpire($origin->getExpire());
		$this->poll->setAnonymous($origin->getAnonymous());
		$this->poll->setFullAnonymous($origin->getFullAnonymous());
		$this->poll->setAllowMaybe($origin->getAllowMaybe());
		$this->poll->setVoteLimit($origin->getVoteLimit());
		$this->poll->setSettings($origin->getSettings());
		$this->poll->setOptions($origin->getOptions());
		$this->poll->setShowResults($origin->getShowResults());
		$this->poll->setAdminAccess($origin->getAdminAccess());
		$this->poll->setImportant($origin->getImportant());

		return $this->pollMapper->insert($this->poll);
	}

	/**
	 * 	 * Collect email addresses from particitipants
	 *
	 * @return string[]
	 *
	 * @psalm-return array<int, string>
	 */
	public function getParticipantsEmailAddresses(int $pollId): array {
		$this->poll = $this->pollMapper->find($pollId);
		$this->acl->setPoll($this->poll)->requestEdit();

		$votes = $this->voteMapper->findParticipantsByPoll($pollId);
		$list = [];
		foreach ($votes as $vote) {
			$list[] = $vote->getDisplayName() . ' <' . $this->mailService->resolveEmailAddress($pollId, $vote->getUserId()) . '>';
		}
		return array_unique($list);
	}

	/**
	 * 	 * Get valid values for configuration options
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
	 * 	 * Get valid values for pollType
	 *
	 * @return string[]
	 *
	 * @psalm-return array{0: string, 1: string}
	 */
	private function getValidPollType(): array {
		return [Poll::TYPE_DATE, Poll::TYPE_TEXT];
	}

	/**
	 * 	 * Get valid values for access
	 *
	 * @return string[]
	 *
	 * @psalm-return array{0: string, 1: string}
	 */
	private function getValidAccess(): array {
		return [Poll::ACCESS_HIDDEN, Poll::ACCESS_PUBLIC];
	}

	/**
	 * 	 * Get valid values for showResult
	 *
	 * @return string[]
	 *
	 * @psalm-return array{0: string, 1: string, 2: string}
	 */
	private function getValidShowResults(): array {
		return [Poll::SHOW_RESULTS_ALWAYS, Poll::SHOW_RESULTS_CLOSED, Poll::SHOW_RESULTS_NEVER];
	}
}
