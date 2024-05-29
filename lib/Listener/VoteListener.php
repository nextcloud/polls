<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Listener;

use OCA\Polls\Db\Watch;
use OCA\Polls\Event\VoteEvent;
use OCA\Polls\Exceptions\InvalidClassException;

class VoteListener extends BaseListener {
	protected const WATCH_TABLES = [
		Watch::OBJECT_VOTES,
		Watch::OBJECT_OPTIONS,
	];

	protected function checkClass() : void {
		if (!($this->event instanceof VoteEvent)) {
			throw new InvalidClassException;
		}
	}
}
