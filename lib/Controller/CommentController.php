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

use OCP\IGroupManager;

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Service\AnonymizeService;
use OCA\Polls\Model\Acl;



class CommentController extends Controller {

	private $userId;
	private $mapper;

	private $groupManager;
	private $eventMapper;
	private $anonymizer;
	private $acl;

	/**
	 * CommentController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param CommentMapper $mapper
	 * @param IGroupManager $groupManager
	 * @param EventMapper $eventMapper
	 * @param AnonymizeService $anonymizer
	 * @param Acl $acl
	 */

	public function __construct(
		string $appName,
		$UserId,
		IRequest $request,
		CommentMapper $mapper,
		IGroupManager $groupManager,
		EventMapper $eventMapper,
		AnonymizeService $anonymizer,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
		$this->groupManager = $groupManager;
		$this->eventMapper = $eventMapper;
		$this->anonymizer = $anonymizer;
		$this->acl = $acl;
	}


	/**
	 * get
	 * Read all comments of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {

		try {
			$comments = $this->mapper->findByPoll($pollId);
			if (!$this->acl->setPollId($pollId)->getAllowSeeUsernames()) {
				$comments = $this->anonymizer->getAnonymizedList($comments, $pollId, \OC::$server->getUserSession()->getUser()->getUID());
			}

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse((array) $comments, Http::STATUS_OK);

	}

	/**
	 * get
	 * Read all comments of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function getByToken($token) {
		$this->acl->setToken($token);

		try {
			$comments = $this->mapper->findByPoll($this->acl->getPollId());
			if (!$this->acl->getAllowSeeUsernames()) {
				$comments = $this->anonymizer->getAnonymizedList($comments, $this->acl->getPollId(), $this->acl->getUserId());
			}

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse((array) $comments, Http::STATUS_OK);

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
		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		$time = date('Y-m-d H:i:s');
		$comment = new Comment();
		$comment->setPollId($pollId);
		$comment->setUserId($this->userId);
		$comment->setComment($message);
		$comment->setDt($time);

		try {
			$comment = $this->mapper->insert($comment);
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
		}

		return new DataResponse($comment, Http::STATUS_OK);

	}

	/**
	 * delete
	 * Delete Comment
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $message
	 * @return DataResponse
	 */
	public function delete($comment, $userId) {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$comment = $this->mapper->delete($comment['id']);
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
		}

		return new DataResponse($comment, Http::STATUS_OK);

	}
}
