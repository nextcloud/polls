<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Listener;

use OCA\Polls\Db\Watch;
use OCA\Polls\Event\ShareEvent;
use OCA\Polls\Exceptions\InvalidClassException;

class ShareListener extends BaseListener {
	protected const WATCH_TABLES = [
		Watch::OBJECT_SHARES,
		Watch::OBJECT_POLLS,
		Watch::OBJECT_VOTES,
		Watch::OBJECT_OPTIONS,
		Watch::OBJECT_COMMENTS
	];

	protected function checkClass() : void {
		if (!($this->event instanceof ShareEvent)) {
			throw new InvalidClassException;
		}
	}
}
