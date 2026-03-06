<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollGroup;
use OCA\Polls\Db\PollGroupMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\ISession;
use OCP\Server;

class PollGroupMapperTest extends UnitTestCase {
	private PollGroupMapper $pollGroupMapper;
	private PollMapper $pollMapper;
	/** @var Poll[] $polls */
	private array $polls = [];
	/** @var PollGroup[] $groups */
	private array $groups = [];

	protected function setUp(): void {
		parent::setUp();
		$session = Server::get(ISession::class);
		$session->set('ncPollsUserId', 'TestUser');

		$this->pollGroupMapper = Server::get(PollGroupMapper::class);
		$this->pollMapper = Server::get(PollMapper::class);

		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$this->polls[] = $this->pollMapper->insert($poll);

		for ($count = 0; $count < 2; $count++) {
			/** @var PollGroup $group */
			$group = $this->fm->instance('OCA\Polls\Db\PollGroup');
			$this->groups[] = $this->pollGroupMapper->add($group);
		}
	}

	public function testList(): void {
		$list = $this->pollGroupMapper->list();
		$this->assertGreaterThan(0, count($list));
		$this->assertContainsOnlyInstancesOf(PollGroup::class, $list);
	}

	public function testFind(): void {
		foreach ($this->groups as $group) {
			$this->assertInstanceOf(PollGroup::class, $this->pollGroupMapper->find($group->getId()));
		}
	}

	public function testAddPollToGroup(): void {
		$this->expectNotToPerformAssertions();
		$this->pollGroupMapper->addPollToGroup($this->polls[0]->getId(), $this->groups[0]->getId());
	}

	public function testRemovePollFromGroup(): void {
		$this->expectNotToPerformAssertions();
		$this->pollGroupMapper->addPollToGroup($this->polls[0]->getId(), $this->groups[0]->getId());
		$this->pollGroupMapper->removePollFromGroup($this->polls[0]->getId(), $this->groups[0]->getId());
	}

	public function testTidyPollGroups(): void {
		// groups have no associated polls → tidyPollGroups should delete them all
		$this->pollGroupMapper->tidyPollGroups();
		$this->groups = [];
		$this->expectNotToPerformAssertions();
	}

	public function testUpdate(): void {
		foreach ($this->groups as &$group) {
			$group->setTitle('Updated Title');
			$this->assertInstanceOf(PollGroup::class, $this->pollGroupMapper->update($group));
		}
		unset($group);
	}

	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->groups as $group) {
			try {
				$this->pollGroupMapper->delete($group);
			} catch (\Exception $e) {
				// already deleted (e.g. by testTidyPollGroups)
			}
		}
		foreach ($this->polls as $poll) {
			$this->pollMapper->delete($poll);
		}
	}
}
