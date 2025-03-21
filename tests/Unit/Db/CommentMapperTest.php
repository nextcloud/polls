<?php declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use League\FactoryMuffin\Faker\Facade as Faker;
use OCA\Polls\Tests\Unit\UnitTestCase;

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCP\Server;

class CommentMapperTest extends UnitTestCase {
	private CommentMapper $commentMapper;
	private PollMapper $pollMapper;
	/** @var Poll[] $polls */ 
	private array $polls = [];
	/** @var Comment[] $comments */ 
	private array $comments = [];

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->commentMapper = Server::get(CommentMapper::class);
		$this->pollMapper = Server::get(PollMapper::class);

		$this->polls = [
			$this->fm->instance('OCA\Polls\Db\Poll')
		];

		foreach ($this->polls as &$poll) {
			$poll = $this->pollMapper->insert($poll);

			for ($count=0; $count < 2; $count++) {
				$comment = $this->fm->instance('OCA\Polls\Db\Comment');
				$comment->setPollId($poll->getId());
				array_push($this->comments, $this->commentMapper->insert($comment));
			}
		}
		unset($poll);
	}

	 /**
 	 * testFind
 	 */
 	public function testFind() {
 		foreach ($this->comments as $comment) {
 			$this->assertInstanceOf(Comment::class, $this->commentMapper->find($comment->getId()));
 		}
 	}

	/**
	 * testFindByPoll
	 */
	public function testFindByPoll() {
		foreach ($this->polls as $poll) {
			$this->assertTrue(count($this->commentMapper->findByPoll($poll->getId())) > 0);
		}
	}

	/**
	 * testUpdate
	 */
	public function testUpdate() {
		foreach ($this->comments as &$comment) {
			$newComment = Faker::paragraph();
			$comment->setComment($newComment());
			$this->assertInstanceOf(Comment::class, $this->commentMapper->update($comment));
		}
		unset($comment);
	}

	/**
	 * testDelete
	 */
	public function testDelete() {
		foreach ($this->comments as $comment) {
			$this->assertInstanceOf(Comment::class, $this->commentMapper->delete($comment));
		}
	}

	/**
	 * tearDown
	 */
	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->polls as $poll) {
			$this->pollMapper->delete($poll);
		}
	}

}
