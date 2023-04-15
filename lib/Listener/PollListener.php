<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Listener;

use OCA\Polls\Db\Watch;
use OCA\Polls\Event\PollEvent;
use OCA\Polls\Exceptions\InvalidClassException;

class PollListener extends BaseListener {
	// Simulate vote and option change due to possible configuration changes
	protected const WATCH_TABLES = [
		Watch::OBJECT_POLLS,
		Watch::OBJECT_VOTES,
		Watch::OBJECT_OPTIONS,
	];

	protected function checkClass() : void {
		if (!($this->event instanceof PollEvent)) {
			throw new InvalidClassException;
		}
	}

	protected function createNotification() : void {
		if (!($this->event instanceof PollEvent)) {
			return;
		}
		if (!empty($this->event->getNotification())) {
			$this->notificationService->createNotification($this->event->getNotification());
		}
	}
}
