<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Option;

abstract class OptionEvent extends BaseEvent {
	public const ADD = 'option_add';
	public const UPDATE = 'option_update';
	public const CONFIRM = 'option_confirm';
	public const UNCONFIRM = 'option_unconfirm';
	public const DELETE = 'option_delete';
	public const RESTORE = 'option_restore';

	public function __construct(
		protected Option $option,
	) {
		parent::__construct($option);
		$this->activityObjectType = 'poll';
		$this->activitySubjectParams['optionTitle'] = [
			'type' => 'highlight',
			'id' => (string) $this->option->getId(),
			'name' => $this->option->getPollOptionText(),
		];
	}
}
