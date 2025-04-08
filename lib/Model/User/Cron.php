<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\User;

use OCA\Polls\Model\UserBase;

class Cron extends UserBase {
	/** @var string */
	public const TYPE = 'cron';

	public function __construct() {
		parent::__construct('SYSTEM_CRON_USER', self::TYPE);
	}

	public function getDisplayName(): string {
		return 'Cron Job User';
	}

	public function getIsSystemUser(): bool {
		return true;
	}

}
