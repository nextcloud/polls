<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Service\SubscriptionService;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCA\Polls\UserSession;
use OCP\ISession;
use OCP\IUserManager;
use OCP\Server;

class SubscriptionServiceTest extends UnitTestCase {
	private SubscriptionService $subscriptionService;
	private PollMapper $pollMapper;
	private ISession $session;
	private string $adminOriginalEmail = '';

	private Poll $poll;

	protected function setUp(): void {
		parent::setUp();

		// getAllowSubscribeToPoll() requires getCurrentUser()->getHasEmail().
		// Ensure admin has an email and that the core NC session is active so
		// UserSession::getIsLoggedIn() returns true and getCurrentUser() resolves
		// to the real admin user rather than Ghost.
		$adminUser = Server::get(IUserManager::class)->get('admin');
		$this->adminOriginalEmail = $adminUser?->getEMailAddress() ?? '';
		if ($adminUser !== null && !$adminUser->getEMailAddress()) {
			$adminUser->setEMailAddress('admin@test.local');
		}
		\OC_User::setUserId('admin');

		// Reset the polls UserSession cache so getCurrentUser() is re-resolved
		// from the core session set above, then restore the polls session key.
		$userSession = Server::get(UserSession::class);
		$userSession->cleanSession();

		$this->session = Server::get(ISession::class);
		$this->session->set('ncPollsUserId', 'admin');

		$this->subscriptionService = Server::get(SubscriptionService::class);
		$this->pollMapper = Server::get(PollMapper::class);

		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll->setOwner('admin');
		$this->poll = $this->pollMapper->insert($poll);
	}

	protected function tearDown(): void {
		parent::tearDown();
		Server::get(IUserManager::class)->get('admin')?->setEMailAddress($this->adminOriginalEmail);
		// Subscriptions cascade-delete with the poll
		try {
			$this->pollMapper->delete($this->poll);
		} catch (\Exception) {
		}
	}

	// --- get ---

	public function testGetReturnsFalseWhenNotSubscribed(): void {
		$this->assertFalse($this->subscriptionService->get($this->poll->getId()));
	}

	public function testGetReturnsTrueWhenSubscribed(): void {
		$this->subscriptionService->set(true, $this->poll->getId());
		$this->assertTrue($this->subscriptionService->get($this->poll->getId()));
	}

	// --- set ---

	public function testSetSubscribeReturnsTrue(): void {
		$result = $this->subscriptionService->set(true, $this->poll->getId());
		$this->assertTrue($result);
	}

	public function testSetUnsubscribeWhenNotSubscribedReturnsFalse(): void {
		$result = $this->subscriptionService->set(false, $this->poll->getId());
		$this->assertFalse($result);
	}

	public function testSetUnsubscribeAfterSubscribeReturnsFalse(): void {
		$this->subscriptionService->set(true, $this->poll->getId());
		$result = $this->subscriptionService->set(false, $this->poll->getId());
		$this->assertFalse($result);
	}

	public function testSubscribeTwiceIsIdempotent(): void {
		$this->subscriptionService->set(true, $this->poll->getId());
		// Duplicate insert hits unique constraint → returns true (already subscribed)
		$result = $this->subscriptionService->set(true, $this->poll->getId());
		$this->assertTrue($result);
	}

	public function testGetReturnsFalseAfterUnsubscribe(): void {
		$this->subscriptionService->set(true, $this->poll->getId());
		$this->subscriptionService->set(false, $this->poll->getId());
		$this->assertFalse($this->subscriptionService->get($this->poll->getId()));
	}
}
