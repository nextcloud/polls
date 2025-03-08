<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use DateTime;
use OCA\Polls\AppConstants;
use OCA\Polls\Notification\Notifier;
use OCA\Polls\UserSession;
use OCP\Notification\IManager;

class NotificationService {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		protected IManager $notificationManager,
		protected UserSession $userSession,
	) {
	}

	public function removeNotification(int $pollId): void {
		$notification = $this->notificationManager->createNotification();

		$notification->setApp(AppConstants::APP_ID)
			->setObject('poll', strval($pollId));

		if ($this->userSession->getIsLoggedIn()) {
			// With user context remove only the notifications of the current user
			$notification->setUser($this->userSession->getCurrentUserId());
		}

		$this->notificationManager->markProcessed($notification);
	}

	public function sendInvitation(int $pollId, string $recipient): void {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp(AppConstants::APP_ID)
			->setUser($recipient)
			->setDateTime(new DateTime())
			->setObject('poll', strval($pollId))
			->setSubject(Notifier::NOTIFY_INVITATION, ['pollId' => $pollId, 'recipient' => $recipient]);
		$this->notificationManager->notify($notification);
	}

	public function createNotification(array $params = []): void {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp(AppConstants::APP_ID)
			->setUser($params['recipient'])
			->setDateTime(new DateTime())
			->setObject($params['objectType'], strval($params['objectValue']))
			->setSubject($params['msgId'], $params);
		$this->notificationManager->notify($notification);
	}
}
