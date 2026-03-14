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
		try {
			if ($setToSubscribed) {
				$this->pollMapper->get($pollId)->request(Poll::PERMISSION_POLL_SUBSCRIBE);
				try {
					$this->subscriptionMapper->findByPollAndUser($pollId, $this->userSession->getCurrentUserId());
					// Already subscribed — nothing to do
					return true;
				} catch (DoesNotExistException) {
					$this->add($pollId, $this->userSession->getCurrentUserId());
				}
			} else {
				$subscription = $this->subscriptionMapper->findByPollAndUser($pollId, $this->userSession->getCurrentUserId());
				$this->subscriptionMapper->delete($subscription);
			}

			return $this->get($pollId);
		} catch (DoesNotExistException $e) {
			// No subscription found
			return false;
		} catch (ForbiddenException $e) {
			// Is not allowed to subscribe
			return false;
		}
	}

	private function add(int $pollId, string $userId): void {
		$subscription = new Subscription();
		$subscription->setPollId($pollId);
		$subscription->setUserId($userId);
		$this->subscriptionMapper->insert($subscription);
	}
}
