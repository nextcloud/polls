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

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\IDBConnection;
use League\FactoryMuffin\Faker\Facade as Faker;

class SubscriptionMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;
	/** @var SubscriptionMapper */
	private $subscriptionMapper;
	/** @var EventMapper */
	private $eventMapper;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->subscriptionMapper = new SubscriptionMapper($this->con);
		$this->eventMapper = new EventMapper($this->con);
	}

	/**
	 * Create some fake data and persist them to the database.
	 *
	 * @return Subscription
	 */
	public function testCreate() {
		/** @var Event $event */
		$event = $this->fm->instance('OCA\Polls\Db\Event');
		$this->assertInstanceOf(Event::class, $this->eventMapper->insert($event));

		/** @var Subscription $subscription */
		$subscription = $this->fm->instance('OCA\Polls\Db\Subscription');
		$subscription->setPollId($event->getId());
		$this->assertInstanceOf(Subscription::class, $this->subscriptionMapper->insert($subscription));

		return $subscription;
	}

	/**
	 * Update the previously created entry and persist the changes.
	 *
	 * @depends testCreate
	 * @param Subscription $subscription
	 * @return Subscription
	 */
	public function testUpdate(Subscription $subscription) {
		$newUserId = Faker::firstNameMale();
		$subscription->setUserId($newUserId());
		$this->subscriptionMapper->update($subscription);

		return $subscription;
	}

	/**
	 * Delete the previously created entries from the database.
	 *
	 * @depends testUpdate
	 * @param Subscription $subscription
	 */
	public function testDelete(Subscription $subscription) {
		$event = $this->eventMapper->find($subscription->getPollId());
		$this->subscriptionMapper->delete($subscription);
		$this->eventMapper->delete($event);
	}
}
