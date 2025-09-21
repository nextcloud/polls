<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Exceptions;

class PreconditionTableIsMissingException extends PreconditionException {
	public function __construct(
		string $e = 'Requested Table is missing',
	) {
		parent::__construct($e);
	}
}
