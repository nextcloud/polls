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

use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Model\Acl;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;

class SubscriptionService {
	public function __construct(private SubscriptionMapper $subscriptionMapper) {
	}

	public function get(Acl $acl): bool {
		try {
			$this->subscriptionMapper->findByPollAndUser($acl->getPollId(), $acl->getUserId());
			// Subscription exists
			return true;
		} catch (DoesNotExistException $e) {
			return false;
		}
	}

	public function set(bool $subscribed, Acl $acl): bool {
		if (!$subscribed) {
			try {
				$subscription = $this->subscriptionMapper->findByPollAndUser($acl->getPollId(), $acl->getUserId());
				$this->subscriptionMapper->delete($subscription);
			} catch (DoesNotExistException $e) {
				// catch silently (assume already unsubscribed)
			}
		} else {
			try {
				$this->add($acl->getPollId(), $acl->getUserId());
			} catch (Exception $e) {
				if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					// catch silently (assume already subscribed)
				}
				throw $e;
			}
		}
		return $subscribed;
	}

	private function add(int $pollId, string $userId): void {
		$subscription = new Subscription();
		$subscription->setPollId($pollId);
		$subscription->setUserId($userId);
		$this->subscriptionMapper->insert($subscription);
	}
}
