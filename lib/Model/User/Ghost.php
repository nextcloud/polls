<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\User;

use OCA\Polls\Model\UserBase;

class Ghost extends UserBase {
	public const TYPE = 'deleted';
	public const ICON = 'icon-ghost';

	public function __construct(string $id) {
		parent::__construct($id, self::TYPE);
	}

	public function getDisplayName(): string {
		return 'Deleted User';
	}

}
