<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Exceptions;

use OCP\AppFramework\Http;

class InsufficientAttributesException extends Exception {
	public function __construct(
		string $e = 'Attribut constraints not met',
	) {
		parent::__construct($e, Http::STATUS_CONFLICT);
	}
}
