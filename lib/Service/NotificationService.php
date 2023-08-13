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

use DateTime;
use OCA\Polls\AppConstants;
use OCA\Polls\Notification\Notifier;
use OCP\Notification\IManager;

class NotificationService {
	public function __construct(
		protected IManager $notificationManager,
		protected ?string $userId,
	) {
	}

	public function removeNotification(int $pollId): void {
		$notification = $this->notificationManager->createNotification();
		if (!$this->userId) {
			return;
		}
		$notification->setApp(AppConstants::APP_ID)
			->setObject('poll', strval($pollId))
			->setUser($this->userId);
		$this->notificationManager->markProcessed($notification);
	}

	public function sendInvitation(int $pollId, string $recipient): bool {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp(AppConstants::APP_ID)
			->setUser($recipient)
			->setDateTime(new DateTime())
			->setObject('poll', strval($pollId))
			->setSubject(Notifier::NOTIFY_INVITATION, ['pollId' => $pollId, 'recipient' => $recipient]);
		$this->notificationManager->notify($notification);
		return true;
	}

	public function createNotification(array $params = []): bool {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp(AppConstants::APP_ID)
			->setUser($params['recipient'])
			->setDateTime(new DateTime())
			->setObject($params['objectType'], strval($params['objectValue']))
			->setSubject($params['msgId'], $params);
		$this->notificationManager->notify($notification);
		return true;
	}
}
