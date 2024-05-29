<?php declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Tests\Unit\UnitTestCase;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\SubscriptionMapper;
use OCP\Server;

class SubscriptionMapperTest extends UnitTestCase {
	private SubscriptionMapper $subscriptionMapper;
	private PollMapper $pollMapper;
	/** @var Poll[] $polls */
	private array $polls = [];
	/** @var Subscription[] $subscriptions */ 
	private array $subscriptions = [];
	private array $users = [];

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->subscriptionMapper = Server::get(SubscriptionMapper::class);
		$this->pollMapper = Server::get(PollMapper::class);

		$this->polls = [
			$this->fm->instance('OCA\Polls\Db\Poll')
		];

		foreach ($this->polls as &$poll) {
			$poll = $this->pollMapper->insert($poll);

			for ($count=0; $count < 2; $count++) {
				$subscription = $this->fm->instance('OCA\Polls\Db\Subscription');
				$subscription->setPollId($poll->getId());
				array_push($this->subscriptions, $this->subscriptionMapper->insert($subscription));
			}
			$this->users[$poll->getId()] = $subscription->getUserId();
		}
		unset($poll);
	}

	/**
	 * testFindAllByPoll
	 */
	public function testFindAllByPoll() {
		foreach ($this->polls as $poll) {
			$this->assertTrue(count($this->subscriptionMapper->findAllByPoll($poll->getId())) > 0);
		}
	}

	/**
	 * testfindByPollAndUser
	 */
	public function testfindByPollAndUser() {
		foreach ($this->polls as $poll) {
			$this->assertInstanceOf(Subscription::class, $this->subscriptionMapper->findByPollAndUser($poll->getId(), $this->users[$poll->getId()]));
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
