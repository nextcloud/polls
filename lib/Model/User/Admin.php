<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\User;

class Admin extends User {
	/** @var string */
	public const TYPE = 'admin';

	public function __construct(string $id) {
		parent::__construct($id, self::TYPE);
	}

	public function getPrincipalUri(): string {
		return 'principals/users/' . $this->getId();
	}

}
