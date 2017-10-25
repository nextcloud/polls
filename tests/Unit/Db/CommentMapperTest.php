<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Kai Schröer <kai@schroeer.co>
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
use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCP\IDBConnection;
use OCP\IUserManager;
use PHPUnit_Framework_TestCase;
use League\FactoryMuffin\FactoryMuffin;
use League\FactoryMuffin\Faker\Facade as Faker;

class CommentMapperTest extends PHPUnit_Framework_TestCase {

	/** @var IDBConnection */
	private $con;
	/** @var IUserManager $user */
	private $userManager;
	/** @var CommentMapper */
	private $commentMapper;
	/** @var EventMapper */
	private $eventMapper;
	/** @var FactoryMuffin */
	private	$fm;

	/**
	 * {@inheritDoc}
	 */
	public function setUp() {
		parent::setUp();

		$this->con = \OC::$server->getDatabaseConnection();
		$this->userManager = \OC::$server->getUserManager();
		$this->commentMapper = new CommentMapper($this->con);
		$this->eventMapper = new EventMapper($this->con);

		// Pass the $fm var to the Factories and set class var
		$fm = new FactoryMuffin();
		$fm->loadFactories(__DIR__ . '/../Factories');
		$this->fm = $fm;
	}

	/**
	 * Test the creation of an event and a comment object and save them to the database.
	 *
	 * @return Comment
	 */
	public function testCreate() {
		$user = $this->userManager->createUser(
			strtolower(Faker::unique()->firstNameMale()()),
			Faker::unique()->password()()
		);
		$user->setDisplayName(Faker::firstNameMale()() . ' ' . Faker::firstNameMale()());
		$user->setEMailAddress(Faker::unique()->email()());

		/** @var Event $event */
		$event = $this->fm->instance('OCA\Polls\Db\Event');
		$event->setOwner($user->getUID());
		$this->assertInstanceOf(Event::class, $this->eventMapper->insert($event));

		/** @var Comment $comment */
		$comment = $this->fm->instance('OCA\Polls\Db\Comment');
		$comment->setUserId($user->getUID());
		$comment->setPollId($event->getId());
		$this->assertInstanceOf(Comment::class, $this->commentMapper->insert($comment));

		return $comment;
	}

	/**
	 * Update the previously created comment.
	 *
	 * @depends testCreate
	 * @param Comment $comment
	 * @return Comment
	 */
	public function testUpdate(Comment $comment) {
		$comment->setComment(Faker::sentence(30)());
		$this->commentMapper->update($comment);

		return $comment;
	}

	/**
	 * Delete the previously created entries from the database.
	 *
	 * @depends testCreate
	 * @param Comment $comment
	 */
	public function testDelete(Comment $comment) {
		$event = $this->eventMapper->find($comment->getPollId());
		$this->commentMapper->delete($comment);
		$this->eventMapper->delete($event);
		$user = $this->userManager->get($comment->getUserId());
		$user->delete();
	}
}
