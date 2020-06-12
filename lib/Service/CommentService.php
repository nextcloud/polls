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
use OCA\Polls\Exceptions\NotAuthorizedException;

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



class CommentService {

	private $userId;
	private $commentMapper;
	private $logger;

	private $groupManager;
	private $pollMapper;
	private $anonymizer;
	private $acl;
	private $comment;

	/**
	 * CommentController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param CommentMapper $commentMapper
	 * @param IGroupManager $groupManager
	 * @param PollMapper $pollMapper
	 * @param AnonymizeService $anonymizer
	 * @param Acl $acl
	 */

	public function __construct(
		string $appName,
		$userId,
		IRequest $request,
		ILogger $logger,
		CommentMapper $commentMapper,
		IGroupManager $groupManager,
		PollMapper $pollMapper,
		AnonymizeService $anonymizer,
		Acl $acl
	) {
		$this->userId = $userId;
		$this->commentMapper = $commentMapper;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->pollMapper = $pollMapper;
		$this->anonymizer = $anonymizer;
		$this->acl = $acl;
	}


	/**
	 * get
	 * Read all comments of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @param string $token
	 * @return Array
	 */
	public function get($pollId = 0, $token = '') {
		$this->logger->alert('call commentService->get(' . $pollId . ', '. $token . ')');

		try {
			if ($token && !\OC::$server->getUserSession()->isLoggedIn()) {
				$this->acl->setToken($token);
			} else {
				$this->acl->setPollId($pollId);
			}

			if (!$this->acl->getAllowSeeUsernames()) {
				$this->anonymizer->set($this->acl->getPollId(), $this->acl->getUserId());
				return $this->anonymizer->getComments();
			} else {
				return $this->commentMapper->findByPoll($this->acl->getPollId());
			}

		} catch (Exception $e) {
			$this->logger->alert('Error reading comments for pollId ' . $pollId . ': '. $e);
			throw new DoesNotExistException($e);
		}

	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @param string $message
	 * @param int $pollId
	 * @param string $token
	 * @return Comment
	 */
	public function add($message, $pollId = 0, $token = '') {
		$this->logger->debug('call commentService->write("' . $message . '", ' .$pollId . ', "' .$token . '")');
		try {
			if ($token && !\OC::$server->getUserSession()->isLoggedIn()) {
				$this->acl->setToken($token);
			} else {
				$this->acl->setPollId($pollId);
			}

			if ($this->acl->getAllowComment()) {
				$this->comment = new Comment();
				$this->comment->setPollId($this->acl->getPollId());
				$this->comment->setUserId($this->acl->getUserId());
				$this->comment->setComment($message);
				$this->comment->setDt(date('Y-m-d H:i:s'));
				$this->comment = $this->commentMapper->insert($this->comment);
				return $this->comment;
			} else {
				throw new NotAuthorizedException;
			}

		} catch (Exception $e) {
			$this->logger->alert('Error wrinting comment for pollId ' . $pollId . ': '. $e);
			throw new Exception($e);
		}
	}

	/**
	 * delete
	 * Delete Comment
	 * @NoAdminRequired
	 * @param int $commentId
	 * @param string $token
	 * @return Comment
	 */
	public function delete($commentId, $token = '') {
		$this->logger->debug('call commentService->delete(' . $commentId . ', "' .$token . '")');
		try {
			$this->comment = $this->commentMapper->find($commentId);

			if ($token && !\OC::$server->getUserSession()->isLoggedIn()) {
				$this->acl->setToken($token);
			} else {
				$this->acl->setPollId($this->comment->getPollId());
			}

			if ($this->comment->getUserId() === $this->acl->getUserId()) {
					$this->commentMapper->delete($this->comment);
					return $this->comment;
			} else {
				throw new NotAuthorizedException;
			}
		} catch (\Exception $e) {
			throw new NotAuthorizedException;
		}
	}

}
