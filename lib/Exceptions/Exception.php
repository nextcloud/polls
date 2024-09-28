<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Exceptions;

use OCP\AppFramework\Http;

class Exception extends \Exception {
	public function __construct(
		string $e = 'Unexpected error',
		protected int $status = Http::STATUS_INTERNAL_SERVER_ERROR,
	) {
		parent::__construct($e);
	}

	public function getStatus(): int {
		return $this->status;
	}
}
