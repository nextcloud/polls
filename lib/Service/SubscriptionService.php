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

use Exception;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\ILogger;

use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Model\Acl;

class SubscriptionService  {

	private $userId;
	private $acl;
	private $subscriptionMapper;
	private $logger;

	/**
	 * SubscriptionController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param SubscriptionMapper $subscriptionMapper
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param Acl $acl
	 */

	public function __construct(
		string $appName,
		$userId,
		SubscriptionMapper $subscriptionMapper,
		ILogger $logger,
		Acl $acl
	) {
		$this->userId = $userId;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->acl = $acl;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {
		if (!$this->acl->setPollId($pollId)->getAllowView()) {
			throw new NotAuthorizedException;
		}
		try {
			return $this->subscriptionMapper->findByUserAndPoll($pollId, $this->acl->getUserId());
		} catch (MultipleObjectsReturnedException $e) {
			// subscription should be unique. If duplicates are found resubscribe
			// duplicates are removed in $this->set()
			return $this->set($pollId, true);
		}

	}

	/**
	 * @NoAdminRequired
	 * @param integer $pollId
	 */
	public function set($pollId, $subscribed) {
		if (!$this->acl->setPollId($pollId)->getAllowView()) {
			throw new NotAuthorizedException;
		}
		try {
			$subscription = $this->subscriptionMapper->findByUserAndPoll($pollId, $this->acl->getUserId());
			if (!$subscribed) {
				$this->subscriptionMapper->delete($subscription);
				return 'Unsubscribed';
			} else {
				// subscription already exists, just return the existing subscription
				return $subscription;
			}
		} catch (DoesNotExistException $e){
			if ($subscribed) {
				$subscription = new Subscription();
				$subscription->setPollId($pollId);
				$subscription->setUserId($this->acl->getUserId());

				$this->subscriptionMapper->insert($subscription);
				return $subscription;
			} else {
				// subscription is not found, just approve the unsubscription
				return 'Unsubscribed';
			}
		} catch (MultipleObjectsReturnedException $e) {
			// Duplicates should not exist but if found, fix it
			// unsubscribe from all and resubscribe, if requested
			$this->logger->debug('Multiple subscription (dulpicates) found');
			$this->subscriptionMapper->unsubscribe($pollId, $this->acl->getUserId());
			$this->logger->debug('Unsubscribed all for user ' . $this->acl->getUserId() . 'in poll' . $pollId);
			if ($subscribed) {
				$subscription = new Subscription();
				$subscription->setPollId($pollId);
				$subscription->setUserId($this->acl->getUserId());
				$this->subscriptionMapper->insert($subscription);
				$this->logger->debug('Added new subscription');
				return $subscription;
			} else {
				return 'Unsubscribed';
			}

		}

	}
}
