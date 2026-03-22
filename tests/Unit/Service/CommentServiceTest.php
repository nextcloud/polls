<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Service;

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCA\Polls\UserSession;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;

class CommentServiceTest extends UnitTestCase {
	private CommentService $commentService;
	private CommentMapper $commentMapper;
	private PollMapper $pollMapper;

	private Poll $poll;

	protected function setUp(): void {
		parent::setUp();
		Server::get(IUserSession::class)->setUser(Server::get(IUserManager::class)->get('admin'));
		Server::get(UserSession::class)->cleanSession();

		$this->commentService = Server::get(CommentService::class);
		$this->commentMapper = Server::get(CommentMapper::class);
		$this->pollMapper = Server::get(PollMapper::class);

		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll->setOwner('admin');
		$this->poll = $this->pollMapper->insert($poll);
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Comments cascade-delete with the poll
		try {
			$this->pollMapper->delete($this->poll);
		} catch (\Exception) {
		}
	}

	// --- add ---

	public function testAddCreatesComment(): void {
		$comment = $this->commentService->add('Hello World', $this->poll->getId());
		$this->assertInstanceOf(Comment::class, $comment);
		$this->assertSame('Hello World', $comment->getComment());
		$this->assertSame('admin', $comment->getUserId());
		$this->assertSame($this->poll->getId(), $comment->getPollId());
		$this->assertSame(Comment::CONFIDENTIAL_NO, $comment->getConfidential());
		$this->assertNull($comment->getRecipient());
	}

	public function testAddConfidentialComment(): void {
		$comment = $this->commentService->add('Secret', $this->poll->getId(), true);
		$this->assertSame(Comment::CONFIDENTIAL_YES, $comment->getConfidential());
		// recipient is set to the poll owner for confidential comments
		$this->assertSame('admin', $comment->getRecipient());
	}

	// --- list ---

	public function testListReturnsAddedComments(): void {
		$this->commentService->add('First', $this->poll->getId());
		$this->commentService->add('Second', $this->poll->getId());
		$comments = $this->commentService->list($this->poll->getId());
		$this->assertCount(2, $comments);
	}

	public function testListGroupsRapidCommentsFromSameUser(): void {
		$first = $this->commentService->add('First', $this->poll->getId());
		$second = $this->commentService->add('Second', $this->poll->getId());
		$comments = $this->commentService->list($this->poll->getId());
		// Both posted within 5 minutes by the same user → second gets parent set
		$this->assertSame(0, $comments[0]->getParent());
		$this->assertSame($first->getId(), $comments[1]->getParent());
	}

	public function testListDoesNotReturnDeletedComments(): void {
		$comment = $this->commentService->add('To delete', $this->poll->getId());
		$this->commentService->delete($comment->getId());
		$comments = $this->commentService->list($this->poll->getId());
		$this->assertEmpty($comments);
	}

	// --- delete / restore ---

	public function testDeleteSetsDeletedTimestamp(): void {
		$comment = $this->commentService->add('To delete', $this->poll->getId());
		$deleted = $this->commentService->delete($comment->getId());
		$this->assertGreaterThan(0, $deleted->getDeleted());
	}

	public function testRestoreClearsDeletedTimestamp(): void {
		$comment = $this->commentService->add('To restore', $this->poll->getId());
		$this->commentService->delete($comment->getId());
		$restored = $this->commentService->restore($comment->getId());
		$this->assertSame(0, $restored->getDeleted());
	}

	public function testOwnerCanDeleteForeignComment(): void {
		// Insert a comment directly as a different user
		$foreign = new Comment();
		$foreign->setPollId($this->poll->getId());
		$foreign->setUserId('other_user');
		$foreign->setComment('Foreign comment');
		$foreign->setTimestamp(time());
		$foreign->setConfidential(Comment::CONFIDENTIAL_NO);
		$foreign = $this->commentMapper->insert($foreign);

		// Admin (poll owner) has PERMISSION_COMMENT_DELETE → can delete foreign comment
		$deleted = $this->commentService->delete($foreign->getId());
		$this->assertGreaterThan(0, $deleted->getDeleted());
	}
}
