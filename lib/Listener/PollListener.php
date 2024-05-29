<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
