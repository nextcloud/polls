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
	private $logMapper;

	/** @var PollMapper */
	private $pollMapper;

	/** @var array */
	private $polls;

	/** @var array */
	private $logs;

	/** @var array */
	private $pollsById;

	/** @var array */
	private $logsById;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->logMapper = new LogMapper($this->con);
		$this->pollMapper = new PollMapper($this->con);

		$this->polls = [];
		$this->logs = [];

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
				$log = $this->fm->instance('OCA\Polls\Db\Log');
				$log->setPollId($id);
				array_push($this->logs, $log);
			}
		}

		foreach ($this->logs as $log) {
			$entry = $this->logMapper->insert($log);
			$entry->resetUpdatedFields();
			$this->logsById[$entry->getId()] = $entry;
		}


	}

	/**
	 * Find the previously created entries from the database.
	 */
	public function testFindByPoll() {
		foreach ($this->pollsById as $id => $poll) {
			$this->assertTrue(count($this->logMapper->findByPoll($id)) > 0);
		}
	}

	/**
	 * Find the previously created entries from the database.
	 */
	public function testFindUnprocessed() {
		$this->assertTrue(count($this->logMapper->findUnprocessed()) > 0);
	}

	/**
	 * Find the previously created entries from the database.
	 */
	public function testFindUnprocessedPolls() {
		$this->assertTrue(count($this->logMapper->findUnprocessedPolls()) > 0);
	}

	/**
	 * Find the previously created entries from the database.
	 */
	public function testGetLastRecord() {
		$this->assertInstanceOf(Log::class, $this->logMapper->getLastRecord());
	}

	/**
	 * Delete the previously created entries from the database.
	 */
	public function testDelete() {
		foreach ($this->logsById as $id => $log) {
			$found = $this->logMapper->find($id);
			$this->assertInstanceOf(Log::class, $this->logMapper->delete($found));
		}
	}

	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->polls as $poll) {
			$this->pollMapper->delete($poll);
		}
	}

}
