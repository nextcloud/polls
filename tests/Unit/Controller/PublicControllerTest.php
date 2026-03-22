<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Controller;

use OCA\Polls\Controller\PublicController;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Model\Settings\SystemSettings;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCA\Polls\UserSession;
use OCP\AppFramework\Http;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;

/**
 * Integration tests for PublicController share access use-cases.
 *
 * Use-cases under test:
 *  1. Anonymous (external) user registers on a public share → TYPE_EXTERNAL share returned
 *  2. Logged-in NC user accesses a public share → personal TYPE_USER share returned
 *
 * Middleware is bypassed in unit tests, so the share token and NC user session
 * are set up manually before each assertion.
 */
class PublicControllerTest extends UnitTestCase {
	private PublicController $controller;
	private ShareService $shareService;
	private PollMapper $pollMapper;
	private ISession $session;
	private UserSession $userSession;
	private SystemSettings $originalSystemSettings;

	private Poll $poll;
	private Share $publicShare;
	private IUser $visitor;

	protected function setUp(): void {
		parent::setUp();

		// Replace SystemSettings with a permissive mock so public share creation
		// is not blocked by group-restriction app-config settings in the CI env.
		$this->originalSystemSettings = Server::get(SystemSettings::class);
		$settingsMock = $this->createMock(SystemSettings::class);
		$settingsMock->method('getShareCreateAllowed')->willReturn(true);
		$settingsMock->method('getExternalShareCreationAllowed')->willReturn(true);
		\OC::$server->registerService(SystemSettings::class, fn () => $settingsMock);

		$this->pollMapper = Server::get(PollMapper::class);
		$this->shareService = Server::get(ShareService::class);
		$this->session = Server::get(ISession::class);
		$this->userSession = Server::get(UserSession::class);
		$this->controller = Server::get(PublicController::class);

		// Create a private poll owned by admin
		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll->setOwner('admin');
		$poll->setAccess(Poll::ACCESS_PRIVATE);
		$this->poll = $this->pollMapper->insert($poll);

		// Login as admin to create the public share via the service
		// (createPublicShare checks PERMISSION_POLL_EDIT)
		Server::get(IUserSession::class)->setUser(Server::get(IUserManager::class)->get('admin'));
		$this->userSession->cleanSession();
		$this->publicShare = $this->shareService->createPublicShare($this->poll->getId());

		// Create a separate NC user who is NOT the poll owner.
		// Required for the logged-in access test: if the visitor were the owner,
		// Poll::getIsInvolved() would return true and the public-share code path
		// would be skipped entirely.
		$userManager = Server::get(IUserManager::class);
		$this->visitor = $userManager->createUser('polls_ctrl_visitor', 'password');
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Poll deletion cascades to all associated shares
		try {
			$this->pollMapper->delete($this->poll);
		} catch (\Exception) {
		}
		$this->visitor->delete();
		$original = $this->originalSystemSettings;
		\OC::$server->registerService(SystemSettings::class, fn () => $original);
	}

	/**
	 * Use-case 1: Anonymous user registers on a public share.
	 *
	 * ShareService::register() → registerGuest() creates a TYPE_EXTERNAL share
	 * with a generated userId and the provided displayName/email.
	 */
	public function testRegisterOnPublicShareCreatesExternalShare(): void {
		// No NC session — anonymous/external user
		Server::get(IUserSession::class)->setUser(null);
		$this->userSession->cleanSession();
		$this->session->set(UserSession::SESSION_KEY_SHARE_TOKEN, $this->publicShare->getToken());

		$response = $this->controller->register(
			$this->publicShare->getToken(),
			'GuestName',
			'guest@polls.example.com',
		);

		$this->assertSame(Http::STATUS_CREATED, $response->getStatus());
		$data = $response->getData();
		$this->assertArrayHasKey('share', $data);
		$share = $data['share'];
		$this->assertInstanceOf(Share::class, $share);
		$this->assertSame(Share::TYPE_EXTERNAL, $share->getType());
		$this->assertSame($this->poll->getId(), $share->getPollId());
		$this->assertSame('GuestName', $share->getDisplayName());
	}

	/**
	 * Use-case 2: Logged-in NC user (non-owner) accesses a public share.
	 *
	 * ShareService::getEffectiveShare() detects TYPE_PUBLIC + isLoggedIn()
	 * and calls registerInternalUser() → returns (or creates) a TYPE_USER share.
	 * The visitor must not be the poll owner, otherwise Poll::getIsInvolved()
	 * returns true and the public-share branch is never reached.
	 */
	public function testLoggedInUserAccessingPublicShareGetsUserShare(): void {
		Server::get(IUserSession::class)->setUser($this->visitor);
		$this->userSession->cleanSession();
		$this->session->set(UserSession::SESSION_KEY_SHARE_TOKEN, $this->publicShare->getToken());

		$response = $this->controller->getShare($this->publicShare->getToken());

		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$data = $response->getData();
		$this->assertArrayHasKey('share', $data);
		$share = $data['share'];
		$this->assertInstanceOf(Share::class, $share);
		$this->assertSame(Share::TYPE_USER, $share->getType());
		$this->assertSame($this->visitor->getUID(), $share->getUserId());
		$this->assertSame($this->poll->getId(), $share->getPollId());
	}
}
