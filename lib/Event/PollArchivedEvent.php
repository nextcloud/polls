<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Poll;
use OCA\Polls\Notification\Notifier;

class PollArchivedEvent extends PollEvent {
	public function __construct(
		protected Poll $poll,
	) {
		parent::__construct($poll);
		$this->eventId = self::DELETE;
	}

	public function getNotification(): array {
		if ($this->getActor() === $this->getPollOwner()) {
			return [];
		}

		return [
			'msgId' => Notifier::NOTIFY_POLL_ARCHIVED_BY_OTHER,
			'objectType' => 'poll',
			'objectValue' => $this->getPollId(),
			'recipient' => $this->getPollOwner(),
			'actor' => $this->getActor(),
			'pollTitle' => $this->getPollTitle(),
		];
	}
}
