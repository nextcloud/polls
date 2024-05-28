<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Event\CommentAddEvent;
use OCA\Polls\Event\CommentDeleteEvent;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Model\Acl as Acl;
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
		private PollMapper $pollMapper,
	) {
	}

	/**
	 * Get comments
	 * Read all comments of a poll based on the poll id and return list as array
	 * @return Comment[]
	 */
	public function list(int $pollId): array {
		try {
			$this->pollMapper->find($pollId)->request(Poll::PERMISSION_COMMENT_ADD);
		} catch (Exception $e) {
			return [];
		}
		$this->pollMapper->find($pollId)->request(Poll::PERMISSION_COMMENT_ADD);

		$comments = $this->commentMapper->findByPoll($pollId);
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
	public function add(string $message, int $pollId): Comment {
		$this->pollMapper->find($pollId)->request(Poll::PERMISSION_COMMENT_ADD);

		$this->comment = new Comment();
		$this->comment->setPollId($pollId);
		$this->comment->setUserId($this->acl->getCurrentUserId());
		$this->comment->setComment($message);
		$this->comment->setTimestamp(time());
		$this->comment = $this->commentMapper->insert($this->comment);

		$this->eventDispatcher->dispatchTyped(new CommentAddEvent($this->comment));

		return $this->comment;
	}

	/**
	 * Delete or restore comment
	 * @param int $commentId id of Comment to delete or restore
	 * @param bool $restore Set true, if comment is to be restored
	 */
	public function delete(int $commentId, bool $restore = false): Comment {
		$this->comment = $this->commentMapper->find($commentId);

		if (!$this->acl->matchUser($this->comment->getUserId())) {
			$this->pollMapper->find($this->comment->getPollId())->request(Poll::PERMISSION_COMMENT_DELETE);
		}
	
		$this->comment->setDeleted($restore ? 0 : time());
		$this->commentMapper->update($this->comment);
		$this->eventDispatcher->dispatchTyped(new CommentDeleteEvent($this->comment));

		return $this->comment;
	}
}
