<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollGroup;
use OCA\Polls\Db\PollGroupMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Service\PollGroupService;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCA\Polls\UserSession;
use OCP\Server;

class PollGroupServiceTest extends UnitTestCase {
	private PollGroupService $pollGroupService;
	private PollGroupMapper $pollGroupMapper;
	private PollMapper $pollMapper;
	private UserSession $userSession;

	private Poll $poll;

	protected function setUp(): void {
		parent::setUp();
		$this->userSession = Server::get(UserSession::class);
		\OC_User::setUserId('admin');
		$this->userSession->cleanSession();

		$this->pollGroupService = Server::get(PollGroupService::class);
		$this->pollGroupMapper = Server::get(PollGroupMapper::class);
		$this->pollMapper = Server::get(PollMapper::class);

		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll->setOwner('admin');
		$this->poll = $this->pollMapper->insert($poll);
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Poll groups without polls are cleaned up by tidyPollGroups.
		// The poll itself (and any linked group relations) must be deleted manually.
		try {
			$this->pollMapper->delete($this->poll);
		} catch (\Exception) {
		}
	}

	// --- list ---

	public function testListPollGroupsReturnsArray(): void {
		$groups = $this->pollGroupService->listPollGroups();
		$this->assertIsArray($groups);
	}

	// --- addPollToPollGroup (new group by name) ---

	public function testAddPollToNewGroupCreatesGroup(): void {
		$group = $this->pollGroupService->addPollToPollGroup(
			$this->poll->getId(),
			pollGroupName: 'TestGroup',
		);
		$this->assertInstanceOf(PollGroup::class, $group);
		$this->assertSame('TestGroup', $group->getName());
		$this->assertContains($this->poll->getId(), $group->getPollIds());
	}

	public function testAddPollToExistingGroup(): void {
		// Create a group first via the service
		$group = $this->pollGroupService->addPollToPollGroup(
			$this->poll->getId(),
			pollGroupName: 'ExistingGroup',
		);

		// Create a second poll and add it to the same group
		$poll2 = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll2->setOwner('admin');
		$poll2 = $this->pollMapper->insert($poll2);

		try {
			$updated = $this->pollGroupService->addPollToPollGroup(
				$poll2->getId(),
				pollGroupId: $group->getId(),
			);
			$this->assertContains($poll2->getId(), $updated->getPollIds());
		} finally {
			$this->pollMapper->delete($poll2);
		}
	}

	public function testAddPollWithoutGroupIdOrNameThrows(): void {
		$this->expectException(\OCA\Polls\Exceptions\InsufficientAttributesException::class);
		$this->pollGroupService->addPollToPollGroup($this->poll->getId());
	}

	// --- updatePollGroup ---

	public function testUpdatePollGroupChangesName(): void {
		$group = $this->pollGroupService->addPollToPollGroup(
			$this->poll->getId(),
			pollGroupName: 'OriginalName',
		);

		$updated = $this->pollGroupService->updatePollGroup(
			$group->getId(),
			'UpdatedName',
			'ext',
			'desc',
		);

		$this->assertSame('UpdatedName', $updated->getName());
	}

	public function testUpdatePollGroupThrowsForNonOwner(): void {
		$group = $this->pollGroupService->addPollToPollGroup(
			$this->poll->getId(),
			pollGroupName: 'OwnedGroup',
		);

		// Just remove any user session to trigger the permission check easily.
		// The service only checks that the current user is the owner,
		// it doesn't require a logged-in user
		$this->userSession->cleanSession();
		\OC_User::setUserId(null);

		$this->expectException(ForbiddenException::class);
		$this->pollGroupService->updatePollGroup(
			$group->getId(),
			'HackedName',
			'',
			null,
		);
	}

	// --- removePollFromPollGroup ---

	public function testRemovePollFromGroupLeavingOtherPollsReturnsGroup(): void {
		// Add two polls to the same group
		$group = $this->pollGroupService->addPollToPollGroup(
			$this->poll->getId(),
			pollGroupName: 'MultiPollGroup',
		);

		$poll2 = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll2->setOwner('admin');
		$poll2 = $this->pollMapper->insert($poll2);

		try {
			$this->pollGroupService->addPollToPollGroup($poll2->getId(), pollGroupId: $group->getId());

			// Remove only the first poll; group should survive
			$remaining = $this->pollGroupService->removePollFromPollGroup(
				$this->poll->getId(),
				$group->getId(),
			);
			$this->assertInstanceOf(PollGroup::class, $remaining);
			$this->assertNotContains($this->poll->getId(), $remaining->getPollIds());
		} finally {
			$this->pollMapper->delete($poll2);
		}
	}

	public function testRemoveLastPollDeletesGroupAndReturnsNull(): void {
		$group = $this->pollGroupService->addPollToPollGroup(
			$this->poll->getId(),
			pollGroupName: 'SinglePollGroup',
		);

		// Removing the only poll triggers tidyPollGroups → group is deleted → null returned
		$result = $this->pollGroupService->removePollFromPollGroup(
			$this->poll->getId(),
			$group->getId(),
		);
		$this->assertNull($result);
	}

	public function testRemovePollNotInGroupThrows(): void {
		$group = $this->pollGroupService->addPollToPollGroup(
			$this->poll->getId(),
			pollGroupName: 'GroupForNotFound',
		);

		$poll2 = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll2->setOwner('admin');
		$poll2 = $this->pollMapper->insert($poll2);

		try {
			$this->expectException(NotFoundException::class);
			$this->pollGroupService->removePollFromPollGroup($poll2->getId(), $group->getId());
		} finally {
			$this->pollMapper->delete($poll2);
		}
	}
}
