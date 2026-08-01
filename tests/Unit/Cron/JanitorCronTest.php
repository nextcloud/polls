<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Cron;

use OCA\Polls\Cron\JanitorCron;
use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Server;
use ReflectionMethod;

class JanitorCronTest extends UnitTestCase {
	private JanitorCron $janitorCron;
	private IDBConnection $connection;
	private PollMapper $pollMapper;
	private CommentMapper $commentMapper;
	private OptionMapper $optionMapper;
	private ShareMapper $shareMapper;
	private Poll $poll;

	protected function setUp(): void {
		parent::setUp();
		$this->janitorCron = Server::get(JanitorCron::class);
		$this->connection = Server::get(IDBConnection::class);
		$this->pollMapper = Server::get(PollMapper::class);
		$this->commentMapper = Server::get(CommentMapper::class);
		$this->optionMapper = Server::get(OptionMapper::class);
		$this->shareMapper = Server::get(ShareMapper::class);

		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$this->poll = $this->pollMapper->insert($poll);
	}

	public function tearDown(): void {
		parent::tearDown();
		// comments, options and shares cascade-delete with the poll
		$this->pollMapper->delete($this->poll);
	}

	/**
	 * Entries deleted less than 12 hours ago must survive the janitor run
	 * (undo window), entries deleted more than 12 hours ago get purged.
	 */
	public function testPurgeRespectsTwelveHourUndoWindow(): void {
		$withinUndoWindow = time() - 7200; // deleted 2 hours ago
		$outsideUndoWindow = time() - 46800; // deleted 13 hours ago

		$recentComment = $this->createDeletedComment($withinUndoWindow);
		$oldComment = $this->createDeletedComment($outsideUndoWindow);
		$recentOption = $this->createDeletedOption($withinUndoWindow);
		$oldOption = $this->createDeletedOption($outsideUndoWindow);
		$recentShare = $this->createDeletedShare($withinUndoWindow);
		$oldShare = $this->createDeletedShare($outsideUndoWindow);

		$this->runJanitor();

		$this->assertTrue($this->rowExists(Comment::TABLE, $recentComment->getId()), 'Comment deleted 2 hours ago must survive the 12 hours undo window');
		$this->assertTrue($this->rowExists(Option::TABLE, $recentOption->getId()), 'Option deleted 2 hours ago must survive the 12 hours undo window');
		$this->assertTrue($this->rowExists(Share::TABLE, $recentShare->getId()), 'Share deleted 2 hours ago must survive the 12 hours undo window');

		$this->assertFalse($this->rowExists(Comment::TABLE, $oldComment->getId()), 'Comment deleted 13 hours ago must get purged');
		$this->assertFalse($this->rowExists(Option::TABLE, $oldOption->getId()), 'Option deleted 13 hours ago must get purged');
		$this->assertFalse($this->rowExists(Share::TABLE, $oldShare->getId()), 'Share deleted 13 hours ago must get purged');
	}

	private function createDeletedComment(int $deleted): Comment {
		$comment = $this->fm->instance('OCA\Polls\Db\Comment');
		$comment->setPollId($this->poll->getId());
		$comment->setDeleted($deleted);
		return $this->commentMapper->insert($comment);
	}

	private function createDeletedOption(int $deleted): Option {
		$option = $this->fm->instance('OCA\Polls\Db\Option');
		$option->setPoll($this->poll->getId());
		$option->setDeleted($deleted);
		return $this->optionMapper->insert($option);
	}

	private function createDeletedShare(int $deleted): Share {
		$share = $this->fm->instance('OCA\Polls\Db\Share');
		$share->setPollId($this->poll->getId());
		$share->setDeleted($deleted);
		return $this->shareMapper->insert($share);
	}

	private function runJanitor(): void {
		$run = new ReflectionMethod($this->janitorCron, 'run');
		$run->invoke($this->janitorCron, null);
	}

	private function rowExists(string $table, int $id): bool {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id')
			->from($table)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$result = $qb->executeQuery();
		$row = $result->fetchOne();
		$result->closeCursor();

		return $row !== false;
	}
}
