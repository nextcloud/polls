<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration;

use OCA\Polls\Db\V5\IndexManager;
use OCA\Polls\Db\V5\TableManager;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
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
class Version080301Date20250822153002 extends SimpleMigrationStep {
	private ISchemaWrapper $schema;
	private ?IOutput $output = null;

	public function __construct(
		private TableManager $tableManager,
		private IndexManager $indexManager,
		private IDBConnection $connection,
	) {
	}

	public function name(): string {
		return 'Replaces Polls migration to version 8.3.7';
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
