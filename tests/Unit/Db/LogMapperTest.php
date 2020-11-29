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
	private $polls = [];

	/** @var array */
	private $logs = [];


	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->logMapper = new LogMapper($this->con);
		$this->pollMapper = new PollMapper($this->con);

		$this->polls = [
			$this->fm->instance('OCA\Polls\Db\Poll')
		];

		foreach ($this->polls as &$poll) {
			$poll = $this->pollMapper->insert($poll);

			for ($count=0; $count < 2; $count++) {
				$log = $this->fm->instance('OCA\Polls\Db\Log');
				$log->setPollId($poll->getId());
				array_push($this->logs, $this->logMapper->insert($log));
			}
		}
		unset($poll);
	}

	/**
	 * testFindByPollId
	 */
	public function testFindByPollId() {
		foreach ($this->polls as $poll) {
			$this->assertTrue(count($this->logMapper->findByPollId($poll->getId())) > 0);
		}
	}

	/**
	 * testFindUnprocessed
	 */
	public function testFindUnprocessed() {
		$this->assertTrue(count($this->logMapper->findUnprocessed()) > 0);
	}

	/**
	 * testFindUnprocessedPolls
	 */
	public function testFindUnprocessedPolls() {
		$this->assertTrue(count($this->logMapper->findUnprocessedPolls()) > 0);
	}

	/**
	 * testGetLastRecord
	 */
	public function testGetLastRecord() {
		foreach ($this->polls as $poll) {
			$this->assertInstanceOf(Log::class, $this->logMapper->getLastRecord($poll->getId()));
		}
	}

	/**
	 * testUpdate
	 * includes testFind
	 */
	public function testUpdate() {
		foreach ($this->logs as &$log) {
			$before = $this->logMapper->find($log->getId());
			$this->assertEquals($log, $before);

			$log->setMessageId(Log::MSG_ID_UPDATEPOLL);

			$this->assertEquals($log, $this->logMapper->update($log));
			$this->assertNotEquals($before, $this->logMapper->find($log->getId()));
		}
		unset($log);
	}


	/**
	 * testDelete
	 */
	public function testDelete() {
		foreach ($this->logs as $log) {
			$before = $this->logMapper->find($log->getId());
			$this->assertInstanceOf(Log::class, $this->logMapper->delete($before));
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
