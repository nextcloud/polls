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
use OCP\ILogger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCP\IGroupManager;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Service\AnonymizeService;
use OCA\Polls\Service\LogService;
use OCA\Polls\Model\Acl;

class VoteController extends Controller {

	private $userId;
	private $logger;
	private $mapper;
	private $groupManager;
	private $pollMapper;
	private $shareMapper;
	private $anonymizer;
	private $logService;
	private $acl;

	/**
	 * VoteController constructor.
	 * @param string $appName
	 * @param $userId
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param VoteMapper $mapper
	 * @param IGroupManager $groupManager
	 * @param PollMapper $pollMapper
	 * @param ShareMapper $shareMapper
	 * @param AnonymizeService $anonymizer
	 * @param LogService $logService
	 * @param Acl $acl
	 */
	public function __construct(
		string $appName,
		$UserId,
		IRequest $request,
		ILogger $logger,
		VoteMapper $mapper,
		IGroupManager $groupManager,
		PollMapper $pollMapper,
		ShareMapper $shareMapper,
		AnonymizeService $anonymizer,
		LogService $logService,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->pollMapper = $pollMapper;
		$this->shareMapper = $shareMapper;
		$this->anonymizer = $anonymizer;
		$this->logService = $logService;
		$this->acl = $acl;
	}

	/**
	 * Get all votes of given poll
	 * Read all votes of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {

		try {

			if (!$this->acl->getFoundByToken()) {
				$this->acl->setPollId($pollId);
			}

			if (!$this->acl->getAllowSeeUsernames()) {
				$this->anonymizer->set($pollId, $this->acl->getUserId());
				return new DataResponse((array) $this->anonymizer->getVotes(), Http::STATUS_OK);
			} else {
				return new DataResponse((array) $this->mapper->findByPoll($pollId), Http::STATUS_OK);
			}

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

	}

	/**
	 * set
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @param Array $option
	 * @param string $userId
	 * @param string $setTo
	 * @return DataResponse
	 */
	public function set($pollId, $option, $userId, $setTo) {

		try {
			$vote = $this->mapper->findSingleVote($pollId, $option['pollOptionText'], $userId);
			$vote->setVoteAnswer($setTo);
			$this->mapper->update($vote);

		} catch (DoesNotExistException $e) {
			// Vote does not exist, insert as new Vote
			$vote = new Vote();

			$vote->setPollId($pollId);
			$vote->setUserId($userId);
			$vote->setVoteOptionText($option['pollOptionText']);
			$vote->setVoteOptionId($option['id']);
			$vote->setVoteAnswer($setTo);

			$this->mapper->insert($vote);

		} finally {
			$this->logService->setLog($vote->getPollId(), 'setVote', $vote->getUserId());
			return new DataResponse($vote, Http::STATUS_OK);
		}
	}

	/**
	 * Public functions
	 */

	/**
	 * setByToken
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param Array $option
	 * @param string $setTo
	 * @param string $token
	 * @return DataResponse
	 */
	public function setByToken($option, $setTo, $token) {
		try {
			$this->acl->setToken($token);
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return $this->set($this->acl->getPollId(), $option, $this->acl->getUserId(), $setTo);

	}

	/**
	 * getByToken
	 * Read all votes of a poll based on a share token and return list as array
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function getByToken($token) {

		try {
			$this->acl->setToken($token);
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return $this->get($this->acl->getPollId());

	}

}
