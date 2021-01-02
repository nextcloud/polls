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
	 */
	public function list(int $pollId = 0): array {
		$this->acl->setPollId($pollId)->requestView();

		if ($this->acl->getAllowSeeUsernames()) {
			return $this->commentMapper->findByPoll($this->acl->getPollId());
		} else {
			$this->anonymizer->set($this->acl->getPollId(), $this->acl->getUserId());
			return $this->anonymizer->getComments();
		}
	}

	/**
	 * Add comment
	 */
	public function add(?int $pollId = 0, ?string $token = '', string $message): Comment {
		if ($token) {
			$this->acl->setToken($token)->requestComment();
		} else {
			$this->acl->setPollId($pollId)->requestComment();
		}
		$this->comment = new Comment();
		$this->comment->setPollId($this->acl->getPollId());
		$this->comment->setUserId($this->acl->getUserId());
		$this->comment->setComment($message);
		$this->comment->setDt(date('Y-m-d H:i:s'));
		$this->comment->setTimestamp(time());
		$this->comment = $this->commentMapper->insert($this->comment);
		return $this->comment;
	}

	/**
	 * Delete comment
	 */
	public function delete(int $commentId, string $token = ''): Comment {
		$this->comment = $this->commentMapper->find($commentId);

		if ($token) {
			$this->acl->setToken($token)->validateUserId($this->comment->getUserId());
		} else {
			$this->acl->setPollId($this->comment->getPollId())->validateUserId($this->comment->getUserId());
		}

		$this->commentMapper->delete($this->comment);
		return $this->comment;
	}
}
