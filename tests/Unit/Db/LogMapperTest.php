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

use OCA\Polls\Db\Log;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\IDBConnection;
use League\FactoryMuffin\Faker\Facade as Faker;

class LogMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;
	/** @var LogMapper */
	private $LogMapper;
	/** @var PollMapper */
	private $pollMapper;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->logMapper = new LogMapper($this->con);
		$this->pollMapper = new PollMapper($this->con);
	}

	/**
	 * Create some fake data and persist them to the database.
	 *
	 * @return Log
	 */
	public function testCreate() {
		/** @var Poll $poll */
		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$this->assertInstanceOf(Poll::class, $this->pollMapper->insert($poll));

		/** @var Log $log */
		$log = $this->fm->instance('OCA\Polls\Db\Log');
		$log->setPollId($poll->getId());
		$this->assertInstanceOf(Log::class, $this->logMapper->insert($log));

		return $log;
	}

	/**
	 * Update the previously created entry and persist the changes.
	 *
	 * @depends testCreate
	 * @param Log $log
	 * @return Log
	 */
	public function testUpdate(Log $log) {
		$processed = function() {
			$date = new DateTime('today');
			return $date->getTimestamp();
		};
		$log->setProcessed($processed());
		$this->logMapper->update($log);

		return $log;
	}

	/**
	 * Delete the previously created entries from the database.
	 *
	 * @depends testUpdate
	 * @param Log $log
	 */
	public function testDelete(Log $log) {
		$poll = $this->pollMapper->find($log->getPollId());
		$this->logMapper->delete($log);
		$this->pollMapper->delete($poll);
	}
}
