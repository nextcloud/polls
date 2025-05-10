<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Exceptions;

use OCP\AppFramework\Http;

class AlreadyDeletedException extends Exception {
	public function __construct(
		string $e = 'Not found, assume already deleted',
	) {
		parent::__construct($e, Http::STATUS_OK);
	}
}
