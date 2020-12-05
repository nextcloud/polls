<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Kai Schröer <git@schroeer.co>
 *
 * @author Kai Schröer <git@schroeer.co>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Tests\Unit\Db;

use League\FactoryMuffin\Faker\Facade as Faker;
use OCP\IDBConnection;
use OCA\Polls\Tests\Unit\UnitTestCase;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\SubscriptionMapper;

class SubscriptionMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;

	/** @var SubscriptionMapper */
	private $subscriptionMapper;

	/** @var PollMapper */
	private $pollMapper;

	/** @var array */
	private $polls = [];

	/** @var array */
	private $subscriptions = [];

	/** @var array */
	private $users = [];


	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->subscriptionMapper = new SubscriptionMapper($this->con);
		$this->pollMapper = new PollMapper($this->con);

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
	 * testUnsubscribe
	 */
	public function testUnsubscribe() {
		foreach ($this->polls as $poll) {
			$this->assertNull($this->subscriptionMapper->unsubscribe($poll->getId(), $this->users[$poll->getId()]));
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
