<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\Server;

class ShareMapperTest extends UnitTestCase {
	private ShareMapper $shareMapper;
	private PollMapper $pollMapper;
	/** @var Poll[] $polls */
	private array $polls = [];
	/** @var Share[] $shares */
	private array $shares = [];

	protected function setUp(): void {
		parent::setUp();
		$this->shareMapper = Server::get(ShareMapper::class);
		$this->pollMapper = Server::get(PollMapper::class);

		$this->polls = [
			$this->fm->instance('OCA\Polls\Db\Poll'),
		];

		foreach ($this->polls as &$poll) {
			$poll = $this->pollMapper->insert($poll);

			for ($count = 0; $count < 2; $count++) {
				/** @var Share $share */
				$share = $this->fm->instance('OCA\Polls\Db\Share');
				$share->setPollId($poll->getId());
				array_push($this->shares, $this->shareMapper->insert($share));
			}
		}
		unset($poll);
	}

	public function testFindByPoll(): void {
		foreach ($this->polls as $poll) {
			$this->assertGreaterThan(0, count($this->shareMapper->findByPoll($poll->getId())));
		}
	}

	public function testFindByToken(): void {
		foreach ($this->shares as $share) {
			$this->assertInstanceOf(Share::class, $this->shareMapper->findByToken($share->getToken()));
		}
	}

	public function testFindByPollAndUser(): void {
		foreach ($this->shares as $share) {
			$this->assertInstanceOf(
				Share::class,
				$this->shareMapper->findByPollAndUser($share->getPollId(), $share->getUserId())
			);
		}
	}

	public function testFindByPollNotInvited(): void {
		foreach ($this->polls as $poll) {
			// shares were inserted with invitationSent = 0
			$this->assertGreaterThan(0, count($this->shareMapper->findByPollNotInvited($poll->getId())));
		}
	}

	public function testFindByPollUnreminded(): void {
		foreach ($this->polls as $poll) {
			// reminderSent defaults to 0
			$this->assertGreaterThan(0, count($this->shareMapper->findByPollUnreminded($poll->getId())));
		}
	}

	public function testUpdate(): void {
		foreach ($this->shares as &$share) {
			$share->setDisplayName('Updated Name');
			$this->assertInstanceOf(Share::class, $this->shareMapper->update($share));
		}
		unset($share);
	}

	public function testDelete(): void {
		foreach ($this->shares as $share) {
			$this->assertInstanceOf(Share::class, $this->shareMapper->delete($share));
		}
		$this->shares = [];
	}

	public function testDeleteByIdAndType(): void {
		$this->expectNotToPerformAssertions();
		foreach ($this->shares as $share) {
			$this->shareMapper->deleteByIdAndType($share->getUserId(), $share->getType());
		}
		$this->shares = [];
	}

	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->shares as $share) {
			$this->shareMapper->delete($share);
		}
		foreach ($this->polls as $poll) {
			$this->pollMapper->delete($poll);
		}
	}
}
