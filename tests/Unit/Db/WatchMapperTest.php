<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Watch;
use OCA\Polls\Db\WatchMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCA\Polls\UserSession;
use OCP\Server;

class WatchMapperTest extends UnitTestCase {
	private WatchMapper $watchMapper;
	private PollMapper $pollMapper;
	private UserSession $userSession;
	/** @var Poll[] $polls */
	private array $polls = [];
	/** @var Watch[] $watches */
	private array $watches = [];

	protected function setUp(): void {
		parent::setUp();
		$this->watchMapper = Server::get(WatchMapper::class);
		$this->pollMapper = Server::get(PollMapper::class);
		$this->userSession = Server::get(UserSession::class);

		$this->polls = [
			$this->fm->instance('OCA\Polls\Db\Poll'),
		];

		foreach ($this->polls as &$poll) {
			$poll = $this->pollMapper->insert($poll);

			// A watch with a different session_id: findUpdatesForPollId excludes the
			// current session, so this one will appear in results.
			/** @var Watch $watch */
			$watch = $this->fm->instance('OCA\Polls\Db\Watch');
			$watch->setPollId($poll->getId());
			$watch->setSessionId('test-session-different');
			array_push($this->watches, $this->watchMapper->insert($watch));

			// A watch with the current session's hashed ID for findForPollIdAndTable.
			/** @var Watch $ownWatch */
			$ownWatch = $this->fm->instance('OCA\Polls\Db\Watch');
			$ownWatch->setPollId($poll->getId());
			$ownWatch->setTable(Watch::OBJECT_OPTIONS);
			$ownWatch->setSessionId($this->userSession->getClientIdHashed());
			array_push($this->watches, $this->watchMapper->insert($ownWatch));
		}
		unset($poll);
	}

	public function testInsert(): void {
		foreach ($this->watches as $watch) {
			$this->assertNotNull($watch->getId());
		}
	}

	public function testFindUpdatesForPollId(): void {
		foreach ($this->polls as $poll) {
			$updates = $this->watchMapper->findUpdatesForPollId($poll->getId(), 0);
			// The watch with 'test-session-different' must appear;
			// the own-session watch is filtered out.
			$this->assertGreaterThan(0, count($updates));
		}
	}

	public function testFindForPollIdAndTable(): void {
		foreach ($this->polls as $poll) {
			$this->assertInstanceOf(
				Watch::class,
				$this->watchMapper->findForPollIdAndTable($poll->getId(), Watch::OBJECT_OPTIONS)
			);
		}
	}

	public function testDelete(): void {
		foreach ($this->watches as $watch) {
			$this->assertInstanceOf(Watch::class, $this->watchMapper->delete($watch));
		}
		$this->watches = [];
	}

	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->watches as $watch) {
			$this->watchMapper->delete($watch);
		}
		foreach ($this->polls as $poll) {
			$this->pollMapper->delete($poll);
		}
	}
}
