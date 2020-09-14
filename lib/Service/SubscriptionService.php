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

use OCA\Polls\Exceptions\NotAuthorizedException;
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

	/**
	 * SubscriptionController constructor.
	 * @param SubscriptionMapper $subscriptionMapper
	 * @param Acl $acl
	 */

	public function __construct(
		SubscriptionMapper $subscriptionMapper,
		Acl $acl
	) {
		$this->subscriptionMapper = $subscriptionMapper;
		$this->acl = $acl;
	}

	/**
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return array
	 */
	public function get($pollId, $token) {
		if (!$this->acl->set($pollId, $token)->getAllowView()) {
			throw new NotAuthorizedException;
		}
		try {
			return $this->subscriptionMapper->findByUserAndPoll($this->acl->getPollId(), $this->acl->getUserId());
		} catch (MultipleObjectsReturnedException $e) {
			// subscription should be unique. If duplicates are found resubscribe
			// duplicates are removed in $this->set()
			return $this->set($pollId, $token, true);
		}
	}

	/**
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $token
	 * @param bool $subscribed
	 * @return array
	 */
	public function set($pollId, $token, $subscribed) {
		if (!$this->acl->set($pollId, $token)->getAllowView()) {
			throw new NotAuthorizedException;
		}
		try {
			$subscription = $this->subscriptionMapper->findByUserAndPoll($this->acl->getPollId(), $this->acl->getUserId());
			if (!$subscribed) {
				$this->subscriptionMapper->delete($subscription);
				return ['status' => 'Unsubscribed from poll ' . $this->acl->getPollId()];
			} else {
				// subscription already exists, just return the existing subscription
				return ['status' => 'Subscribed to poll ' . $this->acl->getPollId()];
			}
		} catch (DoesNotExistException $e) {
			if ($subscribed) {
				$subscription = new Subscription();
				$subscription->setPollId($this->acl->getPollId());
				$subscription->setUserId($this->acl->getUserId());

				$this->subscriptionMapper->insert($subscription);
				return ['status' => 'Subscribed to poll ' . $this->acl->getPollId()];
			} else {
				// subscription is not found, just approve the unsubscription
				return ['status' => 'Unsubscribed from poll ' . $this->acl->getPollId()];
			}
		} catch (MultipleObjectsReturnedException $e) {
			// Duplicates should not exist but if found, fix it
			// unsubscribe from all and resubscribe, if requested
			\OC::$server->getLogger()->debug('Multiple subscription (dulpicates) found');
			$this->subscriptionMapper->unsubscribe($this->acl->getPollId(), $this->acl->getUserId());
			\OC::$server->getLogger()->debug('Unsubscribed all for user ' . $this->acl->getUserId() . 'in poll' . $pollId);
			if ($subscribed) {
				$subscription = new Subscription();
				$subscription->setPollId($this->acl->getPollId());
				$subscription->setUserId($this->acl->getUserId());
				$this->subscriptionMapper->insert($subscription);
				\OC::$server->getLogger()->debug('Added new subscription');
				return $subscription;
			} else {
				return ['status' => 'Unsubscribed from poll ' . $this->acl->getPollId()];
			}
		}
	}
}
