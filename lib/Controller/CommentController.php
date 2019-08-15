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
use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Service\AnonymizeService;


class CommentController extends Controller {

	private $mapper;
	private $userId;

	private $groupManager;
	private $eventMapper;
	private $anonymizer;

	public function __construct(
		string $AppName,
		IRequest $request,
		CommentMapper $mapper,
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
	 * get
	 * Read all comments of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {
		$commentsList = array();

		try {
			$event = $this->eventMapper->find($pollId)->read();
			$comments = $this->mapper->findByPoll($pollId);
		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		} finally {
			foreach ($comments as $comment) {
				$commentsList[] = $comment->read();
			}

			if (($event['fullAnonymous'] || ($event['isAnonymous'] && $event['owner'] !== $this->userId))) {
				$commentsList = $this->anonymizer->getAnonymizedList($commentsList, $pollId);
			}
			return new DataResponse($commentsList, Http::STATUS_OK);
		}

	}

	/**
	 * write
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param string $message
	 * @return DataResponse
	 */
	public function write($pollId, $message) {
		if ($this->userId === '') {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$AdminAccess = $this->groupManager->isAdmin($this->userId);
		}

		$time = date('Y-m-d H:i:s');
		$comment = new Comment();
		$comment->setPollId($pollId);
		$comment->setUserId($this->userId);
		$comment->setComment($message);
		$comment->setDt($time);

		try {
			$id = $this->mapper->insert($comment)->getId();
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
		} finally {
			return new DataResponse(array(
				'id' => $id,
				'pollId' => $pollId,
				'userId' => $this->userId,
				'comment' => $message,
				'date' => $time
			), Http::STATUS_OK);
		}

	}
}
