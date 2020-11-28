<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Kai Schröer <git@schroeer.co>
 *
 * @author Kai Schröer <git@schroeer.co>
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

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\IDBConnection;
use League\FactoryMuffin\Faker\Facade as Faker;

class CommentMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;

	/** @var CommentMapper */
	private $commentMapper;

	/** @var PollMapper */
	private $pollMapper;

	/** @var array */
	private $polls;

	/** @var array */
	private $comments;

	/** @var array */
	private $pollsById;

	/** @var array */
	private $commentsById;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->commentMapper = new CommentMapper($this->con);
		$this->pollMapper = new PollMapper($this->con);

		$this->polls = [];
		$this->comments = [];

		$this->polls = [
			$this->fm->instance('OCA\Polls\Db\Poll')
		];

		foreach ($this->polls as $poll) {
			$entry = $this->pollMapper->insert($poll);
			$entry->resetUpdatedFields();
			$this->pollsById[$entry->getId()] = $entry;
		}

		foreach ($this->pollsById as $id => $polls) {
			for ($count=0; $count < 2; $count++) {
				$comment = $this->fm->instance('OCA\Polls\Db\Comment');
				$comment->setPollId($id);
				array_push($this->comments, $comment);
			}
		}

		foreach ($this->comments as $comment) {
			$entry = $this->commentMapper->insert($comment);
			$entry->resetUpdatedFields();
			$this->commentsById[$entry->getId()] = $entry;
		}

	}

	 /**
 	 * Find the previously created entries from the database.
 	 */
 	public function testFind() {
 		foreach ($this->commentsById as $id => $comment) {
 			$this->assertEquals($comment, $this->commentMapper->find($id));
 		}
 	}

	/**
	 * Find the previously created entries from the database.
	 */
	public function testFindByPoll() {
		foreach ($this->pollsById as $id => $poll) {
			$this->assertTrue(count($this->commentMapper->findByPoll($id)) > 0);
		}
	}

	/**
	 * Update the previously created entry and persist the changes.
	 */
	public function testUpdate() {
		foreach ($this->commentsById as $id => $comment) {
			$found = $this->commentMapper->find($id);
			$newComment = Faker::paragraph();
			$found->setComment($newComment());
			$this->assertEquals($found, $this->commentMapper->update($found));
		}
	}

	/**
	 * Delete the previously created entries from the database.
	 */
	public function testDelete() {
		foreach ($this->commentsById as $id => $comment) {
			$found = $this->commentMapper->find($id);
			$this->assertInstanceOf(Comment::class, $this->commentMapper->delete($found));
		}
	}

	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->polls as $poll) {
			$this->pollMapper->delete($poll);
		}
	}

}
