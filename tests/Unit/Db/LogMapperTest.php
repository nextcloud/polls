<?php declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Db\Log;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\Server;

class LogMapperTest extends UnitTestCase {
	private LogMapper $logMapper;
	private PollMapper $pollMapper;
	/** @var Log[] $logs*/
	private array $logs = [];
	/** @var Poll[] $polls*/
	private array $polls = [];

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->logMapper = Server::get(LogMapper::class);
		$this->pollMapper = Server::get(PollMapper::class);

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
	 * testFindUnprocessed
	 */
	public function testFindUnprocessed() {
		$this->assertTrue(count($this->logMapper->findUnprocessed()) > 0);
	}

	/**
	 * testUpdate
	 * includes testFind
	 */
	public function testUpdate() {
		foreach ($this->logs as &$log) {
			$log->setMessageId(Log::MSG_ID_UPDATEPOLL);
			$this->assertInstanceOf(Log::class, $this->logMapper->update($log));
		}
		unset($log);
	}


	/**
	 * testDelete
	 */
	public function testDelete() {
		foreach ($this->logs as $log) {
			// $before = $this->logMapper->find($log->getId());
			$this->assertInstanceOf(Log::class, $this->logMapper->delete($log));
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
