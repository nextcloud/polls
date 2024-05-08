<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Listener;

use OCA\Polls\Db\Watch;
use OCA\Polls\Event\OptionEvent;
use OCA\Polls\Exceptions\InvalidClassException;

class OptionListener extends BaseListener {
	// simulate vote change to force recalculating of votes
	protected const WATCH_TABLES = [
		Watch::OBJECT_OPTIONS,
		Watch::OBJECT_VOTES,
	];

	protected function checkClass() : void {
		if (!($this->event instanceof OptionEvent)) {
			throw new InvalidClassException;
		}
	}
}
