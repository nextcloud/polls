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

use \Exception;

use OCP\IGroupManager;
use OCP\ILogger;

use OCA\Polls\Exceptions\NotAuthorizedException;

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Model\Acl;
use OCA\Polls\Service\AnonymizeService;



class CommentService {

	private $userId;
	private $comment;
	private $commentMapper;
	private $logger;
	private $groupManager;
	private $pollMapper;
	private $anonymizer;
	private $acl;

	/**
	 * CommentService constructor.
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
	public function list($pollId = 0, $token = '') {
		$this->logger->debug('call commentService->get(' . $pollId . ', '. $token . ')');

		if (!$this->acl->checkAuthorize($pollId, $token)) {
			throw new NotAuthorizedException;
		}

		if (!$this->acl->getAllowSeeUsernames()) {
			$this->anonymizer->set($this->acl->getPollId(), $this->acl->getUserId());
			return $this->anonymizer->getComments();
		} else {
			return $this->commentMapper->findByPoll($this->acl->getPollId());
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
	public function add($pollId = 0, $message, $token = '') {
		$this->logger->debug('call commentService->write("' . $message . '", ' .$pollId . ', "' .$token . '")');

		if (!$this->acl->checkAuthorize($pollId, $token)) {
			throw new NotAuthorizedException;
		}

		try {
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

		} catch (\Exception $e) {
			$this->logger->alert('Error writing comment for pollId ' . $pollId . ': '. $e);
			throw new NotAuthorizedException($e);
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
		$this->comment = $this->commentMapper->find($commentId);
		if (!$this->acl->checkAuthorize($this->comment->getPollId(), $token) || $this->comment->getUserId() !== $this->acl->getUserId()) {
			throw new NotAuthorizedException;
		}
		$this->commentMapper->delete($this->comment);

		return $this->comment;

	}

}
