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
use OCP\ISession;
use OCP\Server;

class SubscriptionServiceTest extends UnitTestCase {
	private SubscriptionService $subscriptionService;
	private PollMapper $pollMapper;
	private ISession $session;

	private Poll $poll;

	protected function setUp(): void {
		parent::setUp();
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
