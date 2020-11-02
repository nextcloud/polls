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

use OCA\Polls\Exceptions\NotAuthorizedException;

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Model\Acl;

class CommentService {

	/** @var CommentMapper */
	private $commentMapper;

	/** @var Comment */
	private $comment;

	/** @var AnonymizeService */
	private $anonymizer;

	/** @var Acl */
	private $acl;

	/**
	 * CommentService constructor.
	 * @param CommentMapper $commentMapper
	 * @param Comment $comment
	 * @param AnonymizeService $anonymizer
	 * @param Acl $acl
	 */

	public function __construct(
		CommentMapper $commentMapper,
		Comment $comment,
		AnonymizeService $anonymizer,
		Acl $acl
	) {
		$this->commentMapper = $commentMapper;
		$this->comment = $comment;
		$this->anonymizer = $anonymizer;
		$this->acl = $acl;
	}

	/**
	 * Get comments
	 * Read all comments of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $token
	 * @return array
	 * @throws NotAuthorizedException
	 */
	public function list($pollId = 0, $token = '') {
		if (!$this->acl->set($pollId, $token)->getAllowView()) {
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
	 * Add comment
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $message
	 * @param string $token
	 * @return Comment
	 * @throws NotAuthorizedException
	 */
	public function add($pollId = 0, $message, $token = '') {
		if (!$this->acl->set($pollId, $token)->getAllowComment()) {
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
			\OC::$server->getLogger()->alert('Error writing comment for pollId ' . $pollId . ': ' . $e);
			throw new NotAuthorizedException($e);
		}
	}

	/**
	 * Delete comment
	 * @NoAdminRequired
	 * @param int $commentId
	 * @param string $token
	 * @return Comment
	 * @throws NotAuthorizedException
	 */
	public function delete($commentId, $token = '') {
		$this->comment = $this->commentMapper->find($commentId);

		if ($this->acl->set($this->comment->getPollId(), $token)->getUserId() !== $this->acl->getUserId()) {
			throw new NotAuthorizedException;
		}

		$this->commentMapper->delete($this->comment);
		return $this->comment;
	}
}
