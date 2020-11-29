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

use League\FactoryMuffin\Faker\Facade as Faker;
use OCP\IDBConnection;
use OCA\Polls\Tests\Unit\UnitTestCase;

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;

class CommentMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;

	/** @var CommentMapper */
	private $commentMapper;

	/** @var PollMapper */
	private $pollMapper;

	/** @var array */
	private $polls = [];

	/** @var array */
	private $comments = [];

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->commentMapper = new CommentMapper($this->con);
		$this->pollMapper = new PollMapper($this->con);

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
 			$this->assertEquals($comment, $this->commentMapper->find($comment->getId()));
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
	 * includes testFind
	 */
	public function testUpdate() {
		foreach ($this->comments as &$comment) {
			$before = $this->commentMapper->find($comment->getId());
			$this->assertEquals($comment, $before);

			$newComment = Faker::paragraph();

			$comment->setComment($newComment());
			$this->assertEquals($comment, $this->commentMapper->update($comment));
			$this->assertNotEquals($before, $this->commentMapper->find($comment->getId()));
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
