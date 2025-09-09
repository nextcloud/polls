<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Installation class for the polls app.
 * Initial db creation
 * Changed class naming: Version[jjmmpp]Date[YYYYMMDDHHMMSS]
 * Version: jj = major version, mm = minor, pp = patch
 *
 * @psalm-suppress UnusedClass
 */
class Version080300Date20250816201201 extends SimpleMigrationStep {

	public function __construct() {
	}

	public function name(): string {
		return 'Replaceces Polls migration to version 8.3.0';
	}

	public function description(): string {
		return 'Does not do anything';
	}

	/**
	 * This method is executing the actual schema change based on the definition of TableSchema
	 * $schemaClosure The `\Closure` returns an `ISchemaWrapper`
	 * @param IOutput $output
	 * @param \Closure $schemaClosure
	 * @param array $options
	 * @return ISchemaWrapper|null
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
		return null;
	}
}
