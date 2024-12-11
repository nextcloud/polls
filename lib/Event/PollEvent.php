<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Poll;

abstract class PollEvent extends BaseEvent {
	public const ADD = 'poll_add';
	public const UPDATE = 'poll_update';
	public const DELETE = 'poll_delete';
	public const RESTORE = 'poll_restore';
	public const EXPIRE = 'poll_expire';
	public const CLOSE = 'poll_closed';
	public const REOPEN = 'poll_reopened';
	public const OWNER_CHANGE = 'poll_change_owner';
	public const OPTION_REORDER = 'poll_option_reorder';

	public function __construct(
		protected Poll $poll,
	) {
		parent::__construct($poll);
		$this->activityObjectType = 'poll';
	}
}
