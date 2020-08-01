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

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\EmptyTitleException;
use OCA\Polls\Exceptions\InvalidAccessException;
use OCA\Polls\Exceptions\InvalidShowResultsException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Exceptions\NotAuthorizedException;


use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Service\LogService;
use OCA\Polls\Service\MailService;
use OCA\Polls\Model\Acl;

class PollService {

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

	/** @var MailService */
 	private $mailService;

	/** @var Acl */
 	private $acl;

 	/**
 	 * PollController constructor.
 	 * @param PollMapper $pollMapper
 	 * @param Poll $poll
	 * @param VoteMapper $voteMapper
	 * @param Vote $vote
 	 * @param LogService $logService
 	 * @param MailService $mailService
 	 * @param Acl $acl
 	 */

 	public function __construct(
 		PollMapper $pollMapper,
 		Poll $poll,
		VoteMapper $voteMapper,
 		Vote $vote,
		LogService $logService,
		MailService $mailService,
 		Acl $acl
 	) {
 		$this->pollMapper = $pollMapper;
 		$this->poll = $poll;
		$this->voteMapper = $voteMapper;
 		$this->vote = $vote;
		$this->logService = $logService;
		$this->mailService = $mailService;
 		$this->acl = $acl;
 	}


	/**
	 * Get list of polls
	 * @NoAdminRequired
	 * @return array Array of Poll
	 * @throws NotAuthorizedException
	 */

	public function list() {
		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			throw new NotAuthorizedException;
		}

		$pollList = [];

		$polls = $this->pollMapper->findAll();
		// TODO: Not the elegant way. Improvement neccessary
		foreach ($polls as $poll) {
			$combinedPoll = (object) array_merge(
				(array) json_decode(json_encode($poll)), (array) json_decode(json_encode($this->acl->setPollId($poll->getId()))));
			if ($combinedPoll->allowView) {
				$pollList[] = $combinedPoll;
			}
		}

		return $pollList;
	}

	/**
	 * get poll configuration
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return Poll
	 * @throws NotAuthorizedException
	 */
 	public function get($pollId) {

		if (!$this->acl->setPollId($pollId)->getAllowView()) {
			throw new NotAuthorizedException;
		}

		return $this->pollMapper->find($pollId);

 	}

	/**
	 * get poll configuration by token
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return Poll
	 * @throws NotAuthorizedException
	 */
 	public function getByToken($token) {

		if (!$this->acl->setToken($token)->getAllowView()) {
			throw new NotAuthorizedException;
		}

		return $this->pollMapper->find($this->acl->getPollId());

 	}

	/**
	 * Add poll
	 * @NoAdminRequired
	 * @param string $type
	 * @param string $title
	 * @return Poll
	 * @throws NotAuthorizedException
	 * @throws InvalidPollTypeException
	 * @throws EmptyTitleException
	 */

	public function add($type, $title) {
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
		$this->poll->setAccess('hidden');
		$this->poll->setExpire(0);
		$this->poll->setAnonymous(0);
		$this->poll->setFullAnonymous(0);
		$this->poll->setAllowMaybe(0);
		$this->poll->setVoteLimit(0);
		$this->poll->setSettings('');
		$this->poll->setOptions('');
		$this->poll->setShowResults('always');
		$this->poll->setDeleted(0);
		$this->poll->setAdminAccess(0);
		$this->poll = $this->pollMapper->insert($this->poll);

		$this->logService->setLog($this->poll->getId(), 'addPoll');

		return $this->poll;
	}

	/**
	 * Update poll configuration
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param array $poll
	 * @return Poll
	 * @throws NotAuthorizedException
	 * @throws EmptyTitleException
	 * @throws InvalidShowResultsException
	 * @throws InvalidAccessException
	 */

	public function update($pollId, $poll) {

		$this->poll = $this->pollMapper->find($pollId);

		if (!$this->acl->setPollId($this->poll->getId())->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

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
		$this->logService->setLog($this->poll->getId(), 'updatePoll');

		return $this->poll;
	}


	/**
	 * Switch deleted status (move to deleted polls)
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return Poll
	 * @throws NotAuthorizedException
	 */

	public function delete($pollId) {
		$this->poll = $this->pollMapper->find($pollId);

		if (!$this->acl->setPollId($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		if ($this->poll->getDeleted()) {
			$this->poll->setDeleted(0);
		} else {
			$this->poll->setDeleted(time());
		}

		$this->poll = $this->pollMapper->update($this->poll);
		$this->logService->setLog($this->poll->getId(), 'deletePoll');

		return $this->poll;
	}

	/**
	 * Delete poll
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return Poll the deleted poll
	 * @throws NotAuthorizedException
	 */

	public function deletePermanently($pollId) {
		$this->poll = $this->pollMapper->find($pollId);

		if (!$this->acl->setPollId($pollId)->getAllowEdit() || !$this->poll->getDeleted()) {
			throw new NotAuthorizedException;
		}

		return $this->pollMapper->delete($this->poll);
	}

	/**
	 * Clone poll
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return Poll
	 * @throws NotAuthorizedException
	 */
	public function clone($pollId) {

		$origin = $this->pollMapper->find($pollId);
		if (!$this->acl->setPollId($origin->getId())->getAllowView()) {
			throw new NotAuthorizedException;
		}

		$this->poll = new Poll();
		$this->poll->setCreated(time());
		$this->poll->setOwner(\OC::$server->getUserSession()->getUser()->getUID());
		$this->poll->setTitle('Clone of ' . $origin->getTitle());
		$this->poll->setDeleted(0);
		$this->poll->setAccess('hidden');

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

		return $this->pollMapper->insert($this->poll);
	}

	/**
	 * Collect email addresses from particitipants
	 * @NoAdminRequired
	 * @param Array $poll
	 * @return DataResponse
	 */

	public function getParticipantsEmailAddresses($pollId) {
		$this->poll = $this->pollMapper->find($pollId);
		if (!$this->acl->setPollId($pollId)->getAllowEdit()) {
			return [];
		}

		$votes = $this->voteMapper->findParticipantsByPoll($pollId);
		foreach ($votes as $vote) {
			$list[] = $vote->getDisplayName() . ' <' . $this->mailService->resolveEmailAddress($pollId, $vote->getUserId()) . '>';
		}
		return array_unique($list);
	}


	/**
	 * Get valid values for configuration options
	 * @NoAdminRequired
	 * @return array
	 */
	public function getValidEnum() {
		return [
			'pollType' => $this->getValidPollType(),
			'access' => $this->getValidAccess(),
			'showResults' => $this->getValidShowResults()
		];
	}

	/**
	 * Get valid values for pollType
	 * @NoAdminRequired
	 * @return array
	 */
	private function getValidPollType() {
		return ['datePoll', 'textPoll'];
	}

	/**
	 * Get valid values for access
	 * @NoAdminRequired
	 * @return array
	 */
	private function getValidAccess() {
		return ['hidden', 'public'];
	}

	/**
	 * Get valid values for showResult
	 * @NoAdminRequired
	 * @return array
	 */
	private function getValidShowResults() {
		return ['always', 'expired', 'never'];
	}
}
