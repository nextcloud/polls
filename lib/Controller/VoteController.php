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

use Exeption;
use OCP\AppFramework\Db\DoesNotExistException;


use OCP\IRequest;
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

	private $mapper;
	private $userId;

	private $groupManager;
	private $eventMapper;
	private $anonymizer;

	/**
	 * PageController constructor.
	 * @param string $AppName
	 * @param IGroupManager $groupManager
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param string $userId
	 * @param EventMapper $eventMapper
	 * @param VoteMapper $mapper
	 */
	public function __construct(
		string $AppName,
		IRequest $request,
		VoteMapper $mapper,
		$UserId,
		IGroupManager $groupManager,
		EventMapper $eventMapper,
		AnonymizeService $anonymizer
	) {
		parent::__construct($AppName, $request);
		$this->mapper = $mapper;
		$this->userId = $UserId;
		$this->groupManager = $groupManager;
		$this->eventMapper = $eventMapper;
		$this->anonymizer = $anonymizer;
	}

	/**
	 * read
	 * Read all comments of a poll based on the poll id
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Integer $pollId
	 * @return Array
	 */
	public function get($pollId) {
		$commentsList = array();
		$votesList = array();

		try {
			$event = $this->eventMapper->find($pollId)->read();
			$votes = $this->mapper->findByPoll($pollId);
		} catch (DoesNotExistException $e) {
			// return silently
		} finally {
			foreach ($votes as $vote) {
				$votesList[] = $vote->read();
			}

			if (($event['fullAnonymous'] || ($event['isAnonymous'] && $event['owner'] !== $this->userId))) {
				return $this->anonymizer->getAnonymizedList($votesList, $pollId);
			} else {
				return $votesList;
			}
		}

	}

	/**
	 * set (update/create)
	 * @NoAdminRequired
	 * @param Any $pollId
	 * @param Array $option
	 * @param String $userId
	 * @param String $setTo
	 * @return DataResponse
	 */
	public function set($pollId, $option, $userId, $setTo) {
		$vote = new Vote();

		try {
			$vote = $this->mapper->findSingleVote($pollId, $option['text'], $userId);
			$vote->setVoteAnswer($setTo);
			$this->mapper->update($vote);

		} catch (DoesNotExistException $e) {
			// Vote does not exist, insert as new Vote
			$vote = new Vote();

			$vote->setPollId($pollId);
			$vote->setUserId($userId);
			$vote->setVoteOptionText($option['text']);
			$vote->setVoteOptionId($option['id']);
			$vote->setVoteAnswer($setTo);

			$this->mapper->insert($vote);
		} finally {
			return new DataResponse(array(
				'id' => $vote->getId(),
				'pollId' => $vote->getPollId(),
				'userId' => $vote->getUserId(),
				'voteAnswer' => $vote->getVoteAnswer(),
				'voteOptionId' => $vote->getVoteOptionId(),
				'voteOptionText' => $vote->getVoteOptionText()
			), Http::STATUS_OK);

		}
	}

	/**
	 * write (update/create)
	 * @NoAdminRequired
	 * @param Array $event
	 * @param Array $votes
	 * @param String $mode
	 * @param String $currentUser
	 * @return DataResponse
	 */
	public function write($pollId, $votes, $currentUser) {
		if ($this->userId === '') {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$AdminAccess = $this->groupManager->isAdmin($this->userId);
		}

		$this->mapper->deleteByPollAndUser($pollId, $this->userId);

		foreach ($votes as $vote) {
			if ($vote['userId'] == $this->userId && $vote['pollId'] == $pollId) {
				$NewVote = new Vote();

				$NewVote->setPollId($pollId);
				$NewVote->setUserId($this->userId);
				$NewVote->setVoteOptionText($vote['voteOptionText']);
				$NewVote->setVoteAnswer($vote['voteAnswer']);

				$this->mapper->insert($NewVote);
			}
		}

		return $this->get($pollId);
	}
}
