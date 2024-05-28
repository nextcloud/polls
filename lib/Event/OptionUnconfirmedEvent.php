<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Option;

class OptionUnconfirmedEvent extends OptionEvent {
	public function __construct(
		protected Option $option
	) {
		parent::__construct($option);
		$this->eventId = self::UNCONFIRM;
	}
}
