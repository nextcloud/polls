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

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Service\AnonymizeService;

class VoteController extends Controller {

	private $userId;
	private $mapper;
	private $logger;
	private $groupManager;
	private $eventMapper;
	private $anonymizer;

	/**
	 * VoteController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param IRequest $request
	 * @param VoteMapper $mapper
	 * @param IGroupManager $groupManager
	 * @param EventMapper $eventMapper
	 * @param AnonymizeService $anonymizer
	 */
	public function __construct(
		string $appName,
		$UserId,
		IRequest $request,
		ILogger $logger,
		VoteMapper $mapper,
		IGroupManager $groupManager,
		EventMapper $eventMapper,
		AnonymizeService $anonymizer
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->eventMapper = $eventMapper;
		$this->anonymizer = $anonymizer;
	}

	/**
	 * Get all votes of given poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {
		$event = $this->eventMapper->find($pollId);

		if (($event->getFullAnonymous() || ($event->getIsAnonymous() && $event->getOwner() !== $this->userId))) {
			$votes = $this->anonymizer->getAnonymizedList($this->mapper->findByPoll($pollId), $pollId);
		} else {
			$votes = $this->mapper->findByPoll($pollId);
		}

		return new DataResponse((array) $votes, Http::STATUS_OK);

	}

	/**
	 * Set vote
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
			return new DataResponse($vote, Http::STATUS_OK);
		}
	}

}
