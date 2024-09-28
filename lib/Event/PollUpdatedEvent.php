<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Poll;

class PollUpdatedEvent extends PollEvent {
	public function __construct(
		protected Poll $poll,
	) {
		parent::__construct($poll);
		$this->eventId = self::UPDATE;
	}
}
