<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Exceptions;

use OCP\AppFramework\Http;

class EmptyTitleException extends Exception {
	public function __construct(
		string $e = 'Poll title must not be empty',
	) {
		parent::__construct($e, Http::STATUS_CONFLICT);
	}
}
