<?php
/*
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Event;

use OCA\Polls\Notification\Notifier;

use OCA\Polls\Db\Poll;

class PollDeletedEvent extends PollEvent {
	public function __construct(
		Poll $poll
	) {
		parent::__construct($poll);
		$this->activitySubject = self::DELETE;
	}


	public function getNotification(): array {
		if ($this->getActor() === $this->getPollOwner()) {
			return [];
		}

		return [
			'msgId' => Notifier::NOTIFY_POLL_DELETED_BY_OTHER,
			'objectType' => 'poll',
			'objectValue' => $this->getPollId(),
			'recipient' => $this->getPollOwner(),
			'actor' => $this->getActor(),
			'pollTitle' => $this->getPollTitle(),
		];
	}
}
