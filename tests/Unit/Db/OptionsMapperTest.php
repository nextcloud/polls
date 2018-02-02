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

use OCA\Polls\Db\Options;
use OCA\Polls\Db\OptionsMapper;
use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\IDBConnection;
use League\FactoryMuffin\Faker\Facade as Faker;

class DateMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;
	/** @var OptionsMappper */
	private $optionsMappper;
	/** @var EventMapper */
	private $eventMapper;

	/**
	 * {@inheritDoc}
	 */
	public function setUp() {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->optionsMappper = new OptionsMappper($this->con);
		$this->eventMapper = new EventMapper($this->con);
	}

	/**
	 * Create some fake data and persist them to the database.
	 *
	 * @return Date
	 */
	public function testCreate() {
		/** @var Event $event */
		$event = $this->fm->instance('OCA\Polls\Db\Event');
		$this->assertInstanceOf(Event::class, $this->eventMapper->insert($event));

		/** @var Date $date */
		$option = $this->fm->instance('OCA\Polls\Db\Date');
		$options->setPollId($event->getId());
		$this->assertInstanceOf(Options::class, $this->optionsMappper->insert($options));

		return $options;
	}

	/**
	 * Update the previously created entry and persist the changes.
	 *
	 * @depends testCreate
	 * @param Date $date
	 * @return Date
	 */
	public function testUpdate(Text $text) {
		$newText = Faker::paragraph();
		$text->setPollOptionText($newText());
		$this->optionsMapper->update($text);

		return $text;
	}
	
	/**
	 * Delete the previously created entries from the database.
	 *
	 * @depends testUpdate
	 * @param Date $date
	 */
	public function testDelete(Date $date) {
		$event = $this->eventMapper->find($date->getPollId());
		$this->optionsMappper->delete($date);
		$this->eventMapper->delete($event);
	}
}
