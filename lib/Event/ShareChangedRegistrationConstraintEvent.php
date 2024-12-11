<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Share;

class ShareChangedRegistrationConstraintEvent extends ShareEvent {
	public function __construct(
		protected Share $share,
	) {
		parent::__construct($share);
		$this->eventId = self::CHANGE_REG_CONSTR;
	}
}
