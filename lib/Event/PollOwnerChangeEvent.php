<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Poll;
use OCA\Polls\Notification\Notifier;

class PollOwnerChangeEvent extends PollEvent {
	public function __construct(
		protected Poll $poll,
		protected string $oldOwner,
		protected string $newOwner,
	) {
		parent::__construct($poll);
		$this->eventId = self::OWNER_CHANGE;
	}
	public function getNotification(): array {
		return [
			'msgId' => Notifier::NOTIFY_POLL_CHANGED_OWNER,
			'objectType' => 'poll',
			'objectValue' => $this->getPollId(),
			'recipient' => $this->oldOwner,
			'newOwner' => $this->newOwner,
			'actor' => $this->getActor(),
			'pollTitle' => $this->getPollTitle(),
		];
	}
}
