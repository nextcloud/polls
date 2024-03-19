<?php

declare(strict_types=1);
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
use OCA\Polls\Event\CommentAddEvent;
use OCA\Polls\Event\CommentDeleteEvent;
use OCA\Polls\Model\Acl;
use OCP\EventDispatcher\IEventDispatcher;

class CommentService {
	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private CommentMapper $commentMapper,
		private Comment $comment,
		private IEventDispatcher $eventDispatcher,
		protected Acl $acl,
	) {
	}

	/**
	 * Get comments
	 * Read all comments of a poll based on the poll id and return list as array
	 * @return Comment[]
	 */
	public function list(?int $pollId = null): array {
		if ($pollId !== null) {
			$this->acl->setPollId($pollId);
		}
		$this->acl->request(Acl::PERMISSION_COMMENT_ADD);

		$comments = $this->commentMapper->findByPoll($this->acl->getPoll()->getId());
		// treat comments from the same user within 5 minutes as grouped comments
		$timeTolerance = 5 * 60;
		// init predecessor as empty Comment
		$predecessor = new Comment();

		foreach ($comments as &$comment) {
			if ($comment->getUserId() === $predecessor->getUserId() && $comment->getTimestamp() - $predecessor->getTimestamp() < $timeTolerance) {
				$comment->setParent($predecessor->getId());
			} else {
				$predecessor = $comment;
			}
		}

		return $comments;
	}

	/**
	 * Add comment
	 */
	public function get(int $commentId): Comment {
		return $this->commentMapper->find($commentId);
	}
	/**
	 * Add comment
	 */
	public function add(string $message, ?int $pollId = null): Comment {
		if ($pollId !== null) {
			$this->acl->setPollId($pollId);
		}
		$this->acl->request(Acl::PERMISSION_COMMENT_ADD);

		$this->comment = new Comment();
		$this->comment->setPollId($this->acl->getPoll()->getId());
		$this->comment->setUserId($this->acl->getUserId());
		$this->comment->setComment($message);
		$this->comment->setTimestamp(time());
		$this->comment = $this->commentMapper->insert($this->comment);

		$this->eventDispatcher->dispatchTyped(new CommentAddEvent($this->comment));

		return $this->comment;
	}

	/**
	 * Delete or restore comment
	 * @param Comment $comment Comment to delete or restore
	 * @param bool $restore Set true, if comment is to be restored
	 */
	public function delete(Comment $comment, bool $restore = false): Comment {
		$this->acl->setPollId($comment->getPollId());

		if (!$this->acl->matchUser($comment->getUserId())) {
			$this->acl->request(Acl::PERMISSION_COMMENT_DELETE);
		}
	
		$comment->setDeleted($restore ? 0 : time());
		$this->commentMapper->update($comment);
		$this->eventDispatcher->dispatchTyped(new CommentDeleteEvent($comment));

		return $comment;
	}
}
