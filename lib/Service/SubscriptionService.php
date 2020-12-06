<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Service;

use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\DoesNotExistException;

use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Model\Acl;

class SubscriptionService {

	/** @var Acl */
	private $acl;

	/** @var SubscriptionMapper */
	private $subscriptionMapper;

	public function __construct(
		SubscriptionMapper $subscriptionMapper,
		Acl $acl
	) {
		$this->subscriptionMapper = $subscriptionMapper;
		$this->acl = $acl;
	}

	public function get(int $pollId = 0, string $token = ''): bool {
		if ($token) {
			$this->acl->setToken($token);
		} else {
			$this->acl->setPollId($pollId)->requestView();
		}

		try {
			$this->subscriptionMapper->findByPollAndUser($this->acl->getPollId(), $this->acl->getUserId());
			// Subscription exists
			return true;
		} catch (MultipleObjectsReturnedException $e) {
			// subscription should be unique. If duplicates are found resubscribe
			// duplicates are removed in $this->set()
			return $this->set($pollId, $token, true);
		} catch (DoesNotExistException $e) {
			return false;
		}
	}

	public function add(int $pollId, string $userId): void {
		$subscription = new Subscription();
		$subscription->setPollId($pollId);
		$subscription->setUserId($userId);
		$this->subscriptionMapper->insert($subscription);
	}

	public function set(int $pollId = 0, string $token = '', bool $subscribed): bool {
		if ($token) {
			$this->acl->setToken($token);
		} else {
			$this->acl->setPollId($pollId)->requestView();
		}

		try {
			$subscription = $this->subscriptionMapper->findByPollAndUser($this->acl->getPollId(), $this->acl->getUserId());
			if (!$subscribed) {
				$this->subscriptionMapper->delete($subscription);
			}
		} catch (DoesNotExistException $e) {
			if ($subscribed) {
				// subscription does not exist, add subscription
				$this->add($this->acl->getPollId(), $this->acl->getUserId());
			}
		} catch (MultipleObjectsReturnedException $e) {
			// Duplicates should not exist but if found, fix it
			// unsubscribe from all and resubscribe, if requested
			\OC::$server->getLogger()->debug('Multiple subscription (duplicates) found');
			$this->subscriptionMapper->unsubscribe($this->acl->getPollId(), $this->acl->getUserId());
			\OC::$server->getLogger()->debug('Unsubscribed all for user ' . $this->acl->getUserId() . 'in poll' . $pollId);
			if ($subscribed) {
				$this->add($this->acl->getPollId(), $this->acl->getUserId());
				\OC::$server->getLogger()->debug('Added new subscription');
			}
		}
		return $subscribed;
	}
}
