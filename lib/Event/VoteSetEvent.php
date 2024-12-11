<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Vote;

class VoteSetEvent extends VoteEvent {
	public function __construct(
		protected Vote $vote,
		protected bool $log = true,
	) {
		parent::__construct($vote);
		$this->log = $log;
		$this->eventId = self::SET;
	}
}
