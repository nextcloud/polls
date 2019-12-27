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

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->commentMapper = new CommentMapper($this->con);
		$this->pollMapper = new PollMapper($this->con);
	}

	/**
	 * Create some fake data and persist them to the database.
	 *
	 * @return Comment
	 */
	public function testCreate() {
		/** @var Poll $poll */
		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$this->assertInstanceOf(Poll::class, $this->pollMapper->insert($poll));

		/** @var Comment $comment */
		$comment = $this->fm->instance('OCA\Polls\Db\Comment');
		$comment->setPollId($poll->getId());
		$this->assertInstanceOf(Comment::class, $this->commentMapper->insert($comment));

		return $comment;
	}

	/**
	 * Update the previously created entry and persist the changes.
	 *
	 * @depends testCreate
	 * @param Comment $comment
	 * @return Comment
	 */
	public function testUpdate(Comment $comment) {
		$newComment = Faker::paragraph();
		$comment->setComment($newComment());
		$this->commentMapper->update($comment);

		return $comment;
	}

	/**
	 * Delete the previously created entries from the database.
	 *
	 * @depends testUpdate
	 * @param Comment $comment
	 */
	public function testDelete(Comment $comment) {
		$poll = $this->pollMapper->find($comment->getPollId());
		$this->commentMapper->delete($comment);
		$this->pollMapper->delete($poll);
	}
}
