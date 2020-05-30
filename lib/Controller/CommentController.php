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
use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Service\AnonymizeService;
use OCA\Polls\Model\Acl;



class CommentController extends Controller {

	private $userId;
	private $commentMapper;
	private $comment;
	private $anonymizer;
	private $acl;

	/**
	 * CommentController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param CommentMapper $commentMapper
	 * @param Comment $comment
	 * @param AnonymizeService $anonymizer
	 * @param Acl $acl
	 */

	public function __construct(
		string $appName,
		$userId,
		IRequest $request,
		CommentMapper $commentMapper,
		Comment $comment,
		AnonymizeService $anonymizer,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->commentMapper = $commentMapper;
		$this->comment = $comment;
		$this->anonymizer = $anonymizer;
		$this->acl = $acl;
	}


	/**
	 * get
	 * Read all comments of a poll based on the poll id and return list as array
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
			if (!$this->acl->getAllowSeeUsernames()) {
				$this->anonymizer->set($this->acl->getPollId(), $this->acl->getUserId());
				return new DataResponse((array)
					$this->anonymizer->getComments(),
					Http::STATUS_OK
				);
			} else {
				return new DataResponse((array)
					$this->commentMapper->findByPoll($this->acl->getPollId()),
					Http::STATUS_OK
				);
			}
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @param string $userId
	 * @param string $message
	 * @param string $token
	 * @return DataResponse
	 */
	public function write($pollId, $userId, $message, $token = '') {

		if (\OC::$server->getUserSession()->isLoggedIn()) {
			$this->acl->setPollId($pollId);
		} elseif (!$this->acl->setToken($token)->getTokenIsValid()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		if (!$this->acl->getAllowComment()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		$this->comment = new Comment();
		$this->comment->setPollId($this->acl->getPollId());
		$this->comment->setUserId($this->acl->getUserId());
		$this->comment->setComment($message);
		$this->comment->setDt(date('Y-m-d H:i:s'));

		try {
			$this->comment = $this->commentMapper->insert($this->comment);
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_METHOD_NOT_ALLOWED);
		}
		return $this->list($this->acl->getPollId(), $this->acl->getToken());
	}

	/**
	 * delete
	 * Delete Comment
	 * @PublicPage
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $message
	 * @param string $token
	 * @return DataResponse
	 */
	public function delete($comment, $token = '') {

		$this->comment = $this->commentMapper->find($comment['id']);

		if (\OC::$server->getUserSession()->isLoggedIn()) {
			$this->acl->setPollId($this->comment->getPollId());
		} elseif (!$this->acl->setToken($token)->getTokenIsValid()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			if ($this->comment->getUserId() === $this->acl->getUserId()) {
					$this->comment = $this->commentMapper->delete($this->comment);
			} else {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_METHOD_NOT_ALLOWED);
		}

		return $this->list($this->acl->getPollId(), $this->acl->getToken());
	}
}
