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
use OCA\Polls\Db\Participation;
use OCA\Polls\Db\ParticipationMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\IDBConnection;
use League\FactoryMuffin\Faker\Facade as Faker;

class ParticipationMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;
	/** @var ParticipationMapper */
	private $participationMapper;
	/** @var EventMapper */
	private $eventMapper;

	/**
	 * {@inheritDoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->participationMapper = new ParticipationMapper($this->con);
		$this->eventMapper = new EventMapper($this->con);
	}

	/**
	 * Create some fake data and persist them to the database.
	 *
	 * @return Participation
	 */
	public function testCreate() {
		/** @var Event $event */
		$event = $this->fm->instance('OCA\Polls\Db\Event');
		$this->assertInstanceOf(Event::class, $this->eventMapper->insert($event));

		/** @var Participation $participation */
		$participation = $this->fm->instance('OCA\Polls\Db\Participation');
		$participation->setPollId($event->getId());
		$this->assertInstanceOf(Participation::class, $this->participationMapper->insert($participation));

		return $participation;
	}

	/**
	 * Update the previously created entry and persist the changes.
	 *
	 * @depends testCreate
	 * @param Participation $participation
	 * @return Participation
	 */
	public function testUpdate(Participation $participation) {
		$newDt = Faker::date('Y-m-d H:i:s');
		$participation->setDt($newDt());
		$this->participationMapper->update($participation);

		return $participation;
	}

	/**
	 * Delete the previously created entries from the database.
	 *
	 * @depends testDelete
	 * @param Participation $participation
	 */
	public function testDelete(Participation $participation) {
		$event = $this->eventMapper->find($participation->getPollId());
		$this->participationMapper->delete($participation);
		$this->eventMapper->delete($event);
	}
}
