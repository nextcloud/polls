<?php

declare(strict_types=1);
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

use DateTime;
use OCA\Polls\AppConstants;
use OCA\Polls\Notification\Notifier;
use OCA\Polls\UserSession;
use OCP\Notification\IManager;

class NotificationService {
	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		protected IManager $notificationManager,
		protected UserSession $userSession,
	) {
	}

	public function removeNotification(int $pollId): void {
		$notification = $this->notificationManager->createNotification();
		$userId = $this->userSession->getCurrentUserId();

		$notification->setApp(AppConstants::APP_ID)
			->setObject('poll', strval($pollId))
			->setUser($userId);
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
