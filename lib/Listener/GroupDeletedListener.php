<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Listener;

use OCA\Polls\Cron\GroupDeletedJob;
use OCA\Polls\Exceptions\InvalidClassException;
use OCA\Polls\Exceptions\OCPEventException;
use OCP\Group\Events\GroupDeletedEvent;

class GroupDeletedListener extends BaseListener {
	protected function checkClass() : void {
		if (!($this->event instanceof GroupDeletedEvent)) {
			throw new InvalidClassException;
		}
		throw new OCPEventException;
	}

	protected function addCronJob() : void {
		if (!($this->event instanceof GroupDeletedEvent)) {
			return;
		}
		$this->jobList->add(GroupDeletedJob::class, ['group' => $this->event->getGroup()->getGID()]);
	}
}
