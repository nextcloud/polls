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
	public function __construct(
		private Acl $acl,
		private AnonymizeService $anonymizer,
		private CommentMapper $commentMapper,
		private Comment $comment,
		private IEventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * Get comments
	 * Read all comments of a poll based on the poll id and return list as array
	 *
	 * @return Comment[]
	 *
	 */
	private function listFlat(Acl $acl) : array {
		$comments = $this->commentMapper->findByPoll($acl->getPollId());

		if (!$acl->getIsAllowed(Acl::PERMISSION_POLL_USERNAMES_VIEW)) {
			$this->anonymizer->set($acl->getPollId(), $acl->getUserId());
			$this->anonymizer->anonymize($comments);
		}

		return $comments;
	}

	/**
	 * Get comments
	 * Read all comments of a poll based on the poll id and return list as array
	 */
	public function list(Acl $acl): array {
		$comments = $this->listFlat($acl);
		$timeTolerance = 5 * 60; // treat comments within 5 minutes as one comment
		$tempId = null;
		$tempUserId = null;
		$tempTimestamp = null;

		foreach ($comments as &$comment) {
			if ($comment->getUserId() === $tempUserId && $comment->getTimestamp() - $tempTimestamp < $timeTolerance) {
				$comment->setParent($tempId);
			} else {
				$tempUserId = $comment->getUserId();
				$tempId = $comment->getId();
				$tempTimestamp = $comment->getTimestamp();
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
	public function add(string $message, Acl $acl): Comment {
		$this->comment = new Comment();
		$this->comment->setPollId($acl->getPollId());
		$this->comment->setUserId($acl->getUserId());
		$this->comment->setComment($message);
		$this->comment->setTimestamp(time());
		$this->comment = $this->commentMapper->insert($this->comment);

		$this->eventDispatcher->dispatchTyped(new CommentAddEvent($this->comment));

		return $this->comment;
	}

	/**
	 * Delete or restore comment
	 * @param Comment $comment Comment to delete or restore
	 * @param Acl $acl Acl
	 * @param bool $restore Set true, if comment is to be restored
	 */
	public function delete(Comment $comment, Acl $acl, bool $restore = false): Comment {
		$acl->validatePollId($comment->getPollId());
		if (!$acl->getIsOwner()) {
			$acl->validateUserId($comment->getUserId());
		}
		$comment->setDeleted($restore ? 0 : time());
		$this->commentMapper->update($comment);
		$this->eventDispatcher->dispatchTyped(new CommentDeleteEvent($comment));

		return $comment;
	}
}
