<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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

use OCP\Activity\IManager as ActivityManager;
use OCP\EventDispatcher\Event;
use OCP\Activity\IEvent as ActivityEvent;

class ActivityService {
	public function __construct(
		ActivityManager $activityManager
	) {
		$this->activityManager = $activityManager;
	}

	public function createActivityEvent(Event $event) {
		$activityEvent = $this->activityManager->generateEvent();
		$activityEvent->setApp('polls')
			->setType($event->getActivityMsg())
			->setAuthor($event->getActor())
			->setObject($event->getActivityObjectType(), $event->getActivityObjectId())
			->setSubject($event->getActivitySubject())
			->setTimestamp(time());
		return $activityEvent;
	}

	public function publishActivityEvent(ActivityEvent $activityEvent, string $userId) {
		$activityEvent->setAffectedUser($userId);
		$this->activityManager->publish($activityEvent);
	}
}
