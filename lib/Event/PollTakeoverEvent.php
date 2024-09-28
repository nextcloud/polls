<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Poll;
use OCA\Polls\Notification\Notifier;

class PollTakeoverEvent extends PollOwnerChangeEvent {
	public function __construct(
		protected Poll $poll,
	) {
		parent::__construct($poll);
	}

	public function getNotification(): array {
		return [
			'msgId' => Notifier::NOTIFY_POLL_TAKEOVER,
			'objectType' => 'poll',
			'objectValue' => $this->getPollId(),
			'recipient' => $this->getPollOwner(),
			'actor' => $this->getActor(),
			'pollTitle' => $this->getPollTitle(),
		];
	}
}
