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

	/**
	 * Do not publish vote activities for anonymous polls.
	 * The activity names the voter and is delivered to the poll owner, who could
	 * then match it against the changing results and de-anonymize the vote.
	 * See https://github.com/nextcloud/polls/issues/5192
	 */
	protected function addActivity() : void {
		if ($this->event instanceof VoteEvent && $this->event->isPollAnonymous()) {
			return;
		}
		parent::addActivity();
	}
}
