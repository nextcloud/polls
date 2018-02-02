<?php
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

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Options;
use OCA\Polls\Db\OptionsMapper;
use OCA\Polls\Db\Votes;
use OCA\Polls\Db\VotesMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\IDBConnection;
use League\FactoryMuffin\Faker\Facade as Faker;

class VotesMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;
	/** @var OptionsMapper */
	private $optionsMapper;
	/** @var VotesMapper */
	private $votesMapper;
	/** @var EventMapper */
	private $eventMapper;

	/**
	 * {@inheritDoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->votesMapper = new VotesMapper($this->con);
		$this->eventMapper = new EventMapper($this->con);
	}

	/**
	 * Create some fake data and persist them to the database.
	 *
	 * @return Votes
	 */
	public function testCreate() {
		/** @var Event $event */
		$event = $this->fm->instance('OCA\Polls\Db\Event');
		$this->assertInstanceOf(Event::class, $this->eventMapper->insert($event));

		
		/** @var Votes $votes */
		$votes = $this->fm->instance('OCA\Polls\Db\Votes');
		$votes->setPollId($event->getId());
		$votes->setVoteOptionId(1);
		$this->assertInstanceOf(Votes::class, $this->votesMapper->insert($votes));

		return $votes;
	}

	/**
	 * Update the previously created entry and persist the changes.
	 *
	 * @depends testCreate
	 * @param Votes $votes
	 * @return Votes
	 */
	public function testUpdate(Votes $votes) {
		$newVoteOptionText = Faker::date('Y-m-d H:i:s');
		$votes->setVoteOptionText($newVoteOptionText());
		$this->votesMapper->update($votes);

		return $votes;
	}

	/**
	 * Delete the previously created entries from the database.
	 *
	 * @depends testUpdate
	 * @param Votes $votes
	 */
	public function testDelete(Votes $votes) {
		$event = $this->eventMapper->find($votes->getPollId());
		$this->votesMapper->delete($votes);
		$this->eventMapper->delete($event);
	}
}
