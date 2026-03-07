<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Model\Settings\SystemSettings;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCA\Polls\UserSession;
use OCP\ISession;
use OCP\Server;

class ShareServiceTest extends UnitTestCase {
	private ShareService $shareService;
	private ShareMapper $shareMapper;
	private PollMapper $pollMapper;
	private ISession $session;
	private UserSession $userSession;
	private SystemSettings $originalSystemSettings;

	private Poll $poll;
	private Share $userShare;

	protected function setUp(): void {
		parent::setUp();

		// Save the real SystemSettings and replace it with a permissive mock so
		// that PERMISSION_SHARE_ADD / PERMISSION_SHARE_ADD_EXTERNAL are not
		// blocked by core IAppConfig group-restriction settings in the CI env.
		$this->originalSystemSettings = Server::get(SystemSettings::class);
		$settingsMock = $this->createMock(SystemSettings::class);
		$settingsMock->method('getShareCreateAllowed')->willReturn(true);
		$settingsMock->method('getExternalShareCreationAllowed')->willReturn(true);
		\OC::$server->registerService(SystemSettings::class, fn () => $settingsMock);

		$this->session = Server::get(ISession::class);
		$this->userSession = Server::get(UserSession::class);
		$this->shareService = Server::get(ShareService::class);
		$this->shareMapper = Server::get(ShareMapper::class);
		$this->pollMapper = Server::get(PollMapper::class);

		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll->setOwner('admin');
		$this->poll = $this->pollMapper->insert($poll);

		// Pre-create a TYPE_USER share for admin.
		// Also used to establish a proper logged-in session so that
		// UserSession::getIsLoggedIn() returns true (required for PERMISSION_SHARE_ADD).
		$share = $this->fm->instance('OCA\Polls\Db\Share');
		$share->setPollId($this->poll->getId());
		$share->setType(Share::TYPE_USER);
		$share->setUserId('admin');
		$this->userShare = $this->shareMapper->insert($share);

		$this->login();
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Shares cascade-delete with the poll
		try {
			$this->pollMapper->delete($this->poll);
		} catch (\Exception) {
		}
		// Restore the real SystemSettings so other test classes are unaffected
		$original = $this->originalSystemSettings;
		\OC::$server->registerService(SystemSettings::class, fn () => $original);
	}

	private function login(): void {
		$this->userSession->cleanSession();
		// Set the core Nextcloud user session so IUserSession::isLoggedIn() returns true
		\OC_User::setUserId('admin');
		$this->session->set(UserSession::SESSION_KEY_SHARE_TOKEN, $this->userShare->getToken());
		$this->session->set(UserSession::SESSION_KEY_USER_ID, 'admin');
	}

	// --- add ---

	public function testAddPublicShareCreatesShare(): void {
		$share = $this->shareService->add($this->poll->getId(), Share::TYPE_PUBLIC);
		$this->assertInstanceOf(Share::class, $share);
		$this->assertSame(Share::TYPE_PUBLIC, $share->getType());
		$this->assertSame($this->poll->getId(), $share->getPollId());
		$this->assertNotEmpty($share->getToken());
	}

	public function testAddEmailShareCreatesShare(): void {
		$share = $this->shareService->add(
			$this->poll->getId(),
			Share::TYPE_EMAIL,
			'invited@polls.example.com',
			'Invited Person',
			'invited@polls.example.com',
		);
		$this->assertInstanceOf(Share::class, $share);
		$this->assertSame(Share::TYPE_EMAIL, $share->getType());
		$this->assertSame($this->poll->getId(), $share->getPollId());
	}

	public function testAddDuplicatePublicShareCreatesNewShare(): void {
		$first = $this->shareService->add($this->poll->getId(), Share::TYPE_PUBLIC);
		$second = $this->shareService->add($this->poll->getId(), Share::TYPE_PUBLIC);
		$this->assertNotSame($first->getId(), $second->getId());
	}

	// --- get ---

	public function testGetShareByToken(): void {
		$share = $this->shareService->get($this->userShare->getToken());
		$this->assertInstanceOf(Share::class, $share);
		$this->assertSame($this->userShare->getId(), $share->getId());
	}

	// --- list ---

	public function testListReturnsPollShares(): void {
		$shares = $this->shareService->list($this->poll->getId());
		$this->assertNotEmpty($shares);
		foreach ($shares as $share) {
			$this->assertSame($this->poll->getId(), $share->getPollId());
		}
	}

	// --- delete / restore ---

	public function testDeleteByTokenSetsDeletedTimestamp(): void {
		$deleted = $this->shareService->deleteByToken($this->userShare->getToken());
		$this->assertGreaterThan(0, $deleted->getDeleted());
	}

	public function testRestoreByTokenClearsDeletedTimestamp(): void {
		$this->shareService->deleteByToken($this->userShare->getToken());
		$restored = $this->shareService->deleteByToken($this->userShare->getToken(), restore: true);
		$this->assertSame(0, $restored->getDeleted());
	}

	// --- lock / unlock ---

	public function testLockByTokenSetsLockedTimestamp(): void {
		$locked = $this->shareService->lockByToken($this->userShare->getToken());
		$this->assertGreaterThan(0, $locked->getLocked());
	}

	public function testUnlockByTokenClearsLockedTimestamp(): void {
		$this->shareService->lockByToken($this->userShare->getToken());
		$unlocked = $this->shareService->lockByToken($this->userShare->getToken(), unlock: true);
		$this->assertSame(0, $unlocked->getLocked());
	}

	// --- setType ---

	public function testSetTypeChangesUserShareToAdmin(): void {
		$updated = $this->shareService->setType($this->userShare->getToken(), Share::TYPE_ADMIN);
		$this->assertSame(Share::TYPE_ADMIN, $updated->getType());
	}

	public function testSetTypeChangesAdminShareToUser(): void {
		// Promote to admin first, then demote back
		$this->shareService->setType($this->userShare->getToken(), Share::TYPE_ADMIN);
		$updated = $this->shareService->setType($this->userShare->getToken(), Share::TYPE_USER);
		$this->assertSame(Share::TYPE_USER, $updated->getType());
	}

	public function testSetTypeRejectsInvalidTransition(): void {
		// Only user↔admin transitions are supported; public→admin must not change
		$publicShare = $this->shareService->add($this->poll->getId(), Share::TYPE_PUBLIC);
		$result = $this->shareService->setType($publicShare->getToken(), Share::TYPE_ADMIN);
		// setType silently returns the unchanged share when transition is not allowed
		$this->assertSame(Share::TYPE_PUBLIC, $result->getType());
	}

	// --- setEmailAddress ---

	public function testSetEmailAddressThrowsForNonExternalShare(): void {
		$this->expectException(InvalidShareTypeException::class);
		$this->shareService->setEmailAddress($this->userShare, 'new@example.com');
	}
}
