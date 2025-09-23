<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;

class SubscriptionService {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private SubscriptionMapper $subscriptionMapper,
		private PollMapper $pollMapper,
		private UserSession $userSession,
	) {
	}

	public function get(int $pollId): bool {
		$this->pollMapper->get($pollId)->request(Poll::PERMISSION_POLL_ACCESS);

		try {
			$this->subscriptionMapper->findByPollAndUser($pollId, $this->userSession->getCurrentUserId());
			// Subscription exists
			return true;
		} catch (DoesNotExistException $e) {
			return false;
		} catch (ForbiddenException $e) {
			return false;
		}
	}

	public function set(bool $setToSubscribed, int $pollId): bool {
		if (!$setToSubscribed) {
			// user wants to unsubscribe, allow unsubscribe neverteheless the permissions are set
			try {
				$subscription = $this->subscriptionMapper->findByPollAndUser($pollId, $this->userSession->getCurrentUserId());
				$this->subscriptionMapper->delete($subscription);
			} catch (DoesNotExistException $e) {
				// Not found, assume already unsubscribed
				return false;
			}
		} else {
			try {
				$this->pollMapper->get($pollId)->request(Poll::PERMISSION_POLL_SUBSCRIBE);
				$this->add($pollId, $this->userSession->getCurrentUserId());
			} catch (ForbiddenException $e) {
				return false;
			} catch (Exception $e) {
				if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					// Already subscribed
					return true;
				} else {
					throw $e;
				}
			}
		}
		return $setToSubscribed;
	}

	private function add(int $pollId, string $userId): void {
		$subscription = new Subscription();
		$subscription->setPollId($pollId);
		$subscription->setUserId($userId);
		$this->subscriptionMapper->insert($subscription);
	}
}
