<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Exceptions;

use OCP\AppFramework\Http;

class ForbiddenException extends Exception {
	public function __construct(
		string $e = 'Forbidden',
	) {
		parent::__construct($e, Http::STATUS_FORBIDDEN);
	}
}
