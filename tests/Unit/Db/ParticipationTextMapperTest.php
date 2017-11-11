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
use OCA\Polls\Db\ParticipationText;
use OCA\Polls\Db\ParticipationTextMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\IDBConnection;
use League\FactoryMuffin\Faker\Facade as Faker;

class ParticipationTextMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;
	/** @var ParticipationTextMapper */
	private $participationTextMapper;
	/** @var EventMapper */
	private $eventMapper;

	/**
	 * {@inheritDoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->participationTextMapper = new ParticipationTextMapper($this->con);
		$this->eventMapper = new EventMapper($this->con);
	}

	/**
	 * Create some fake data and persist them to the database.
	 *
	 * @return ParticipationText
	 */
	public function testCreate() {
		/** @var Event $event */
		$event = $this->fm->instance('OCA\Polls\Db\Event');
		$this->assertInstanceOf(Event::class, $this->eventMapper->insert($event));

		/** @var ParticipationText $participationText */
		$participationText = $this->fm->instance('OCA\Polls\Db\ParticipationText');
		$participationText->setPollId($event->getId());
		$this->assertInstanceOf(ParticipationText::class, $this->participationTextMapper->insert($participationText));

		return $participationText;
	}

	/**
	 * Update the previously created entry and persist the changes.
	 *
	 * @depends testCreate
	 * @param ParticipationText $participationText
	 * @return ParticipationText
	 */
	public function testUpdate(ParticipationText $participationText) {
		$newText = Faker::paragraph();
		$participationText->setText($newText());
		$this->participationTextMapper->update($participationText);

		return $participationText;
	}

	/**
	 * Delete the previously created entries from the database.
	 *
	 * @depends testUpdate
	 * @param ParticipationText $participationText
	 */
	public function testDelete(ParticipationText $participationText) {
		$event = $this->eventMapper->find($participationText->getPollId());
		$this->participationTextMapper->delete($participationText);
		$this->eventMapper->delete($event);
	}
}
