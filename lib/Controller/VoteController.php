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

namespace OCA\Polls\Controller;

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;


use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Service\AnonymizeService;
use OCA\Polls\Service\LogService;
use OCA\Polls\Model\Acl;

class VoteController extends Controller {

	private $userId;
	private $voteMapper;
	private $vote;
	private $anonymizer;
	private $logService;
	private $acl;

	/**
	 * VoteController constructor.
	 * @param string $appName
	 * @param $userId
	 * @param IRequest $request
	 * @param VoteMapper $voteMapper
	 * @param Vote $vote
	 * @param AnonymizeService $anonymizer
	 * @param LogService $logService
	 * @param Acl $acl
	 */
	public function __construct(
		string $appName,
		$UserId,
		IRequest $request,
		VoteMapper $voteMapper,
		Vote $vote,
		AnonymizeService $anonymizer,
		LogService $logService,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->voteMapper = $voteMapper;
		$this->vote = $vote;
		$this->anonymizer = $anonymizer;
		$this->logService = $logService;
		$this->acl = $acl;
	}

	/**
	 * list
	 * Get all votes baased on $pollId
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param integer $pollId
	 * @param string $token
	 * @return DataResponse
	 */
	public function list($pollId, $token = '') {

		if (\OC::$server->getUserSession()->isLoggedIn()) {
			$this->acl->setPollId($pollId);
		} elseif (!$this->acl->setToken($token)->getTokenIsValid()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {

			if (!$this->acl->getFoundByToken()) {
				$this->acl->setPollId($pollId);
			}

			if (!$this->acl->getAllowSeeResults()) {
				return new DataResponse((array) $this->voteMapper->findByPollAndUser($pollId, $this->acl->getUserId()), Http::STATUS_OK);
			} elseif (!$this->acl->getAllowSeeUsernames()) {
				$this->anonymizer->set($pollId, $this->acl->getUserId());
				return new DataResponse((array) $this->anonymizer->getVotes(), Http::STATUS_OK);
			} else {
				return new DataResponse((array) $this->voteMapper->findByPoll($pollId), Http::STATUS_OK);
			}

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

	}

	/**
	 * set
	 * change vote
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param integer $pollId - id of poll
	 * @param Array $option - the option to vote on
	 * @param string $setTo - change to state
	 * @param string $token
	 * @return DataResponse
	 */
	public function set($pollId, $option, $setTo, $token = '') {

		if (\OC::$server->getUserSession()->isLoggedIn()) {
			$this->acl->setPollId($pollId);
		} elseif (!$this->acl->setToken($token)->getTokenIsValid()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->vote = $this->voteMapper->findSingleVote(
				$this->acl->getPollId(),
				$option['pollOptionText'],
				$this->acl->getUserId());

			$this->vote->setVoteAnswer($setTo);
			$this->voteMapper->update($this->vote);

		} catch (DoesNotExistException $e) {
			// Vote does not exist, insert as new Vote
			$this->vote = new Vote();

			$this->vote->setPollId($this->acl->getPollId());
			$this->vote->setUserId($this->acl->getUserId());
			$this->vote->setVoteOptionText($option['pollOptionText']);
			$this->vote->setVoteOptionId($option['id']);
			$this->vote->setVoteAnswer($setTo);

			$this->voteMapper->insert($this->vote);

		} finally {
			$this->logService->setLog($this->vote->getPollId(), 'setVote', $this->vote->getUserId());
			return new DataResponse($this->vote, Http::STATUS_OK);
		}
	}


	/**
	 * delete
	 * delete a vote or remove all votes of a poll or a user in a poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $voteId
	 * @param string $userId
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function delete($voteId = 0, $userId = '', $pollId = 0) {
		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			if ($voteId) {
				$this->vote = $this->voteMapper->find($voteId);

				if ($this->acl->setPollId($this->vote->getPollId())->getAllowEdit()) {
					$this->vote = $this->voteMapper->delete($voteId);
					return $this->list($this->vote->getPollId());
				} else {
					return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
				}

			} elseif ($pollId && $userId) {
				if ($this->acl->setPollId($pollId)->getAllowEdit()) {
					$this->votes = $this->voteMapper->deleteByPollAndUser($pollId, $userId);
					return $this->list($pollId);
				} else {
					return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
				}

			} elseif ($pollId) {
				if ($this->acl->setPollId($pollId)->getAllowEdit()) {
					$this->vote = $this->voteMapper->deleteByPoll($pollId);
					return $this->list($pollId);
				} else {
					return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
				}

			} else {
				return new DataResponse($e, Http::STATUS_METHOD_NOT_ALLOWED);
			}
		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}
	}

}
