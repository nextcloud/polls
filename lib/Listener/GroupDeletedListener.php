<?php
/**
 * @copyright Copyright (c) 2021 Jonas Rittershofer <jotoeri@users.noreply.github.com>
 *
 * @author Jonas Rittershofer <jotoeri@users.noreply.github.com>
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

use OCA\Polls\Cron\GroupDeletedJob;
use OCP\Group\Events\GroupDeletedEvent;
use OCA\Polls\Exceptions\InvalidClassException;

class GroupDeletedListener extends BaseListener {
	protected function checkClass() : void {
		if (!($this->event instanceof GroupDeletedEvent)) {
			throw new InvalidClassException;
		}
	}

	protected function addCronJob() : void {
		$this->jobList->add(GroupDeletedJob::class, ['group' => $this->event->getGroup()->getGID()]);
	}
}
