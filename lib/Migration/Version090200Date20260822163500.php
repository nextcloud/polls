<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration;

use OCA\Polls\Db\V11\TableManager;
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
class Version090200Date20260822163500 extends SimpleMigrationStep {
	private ISchemaWrapper $schema;
	private ?IOutput $output = null;

	public function __construct(
		private TableManager $tableManager,
		private IDBConnection $connection,
	) {
	}

	public function name(): string {
		return 'Polls migration to version 9.2.0';
	}

	public function description(): string {
		return 'Migrates Polls\' tables to the current schema';
	}

	/**
	 * This method is called before the schema change.
	 * Removes share duplicates and fixes nullish poll_id/group_id values, so
	 * the NOT NULL constraint can be applied safely in changeSchema() afterwards.
	 * Without this, a still-nullish or duplicated row would make the following
	 * ALTER TABLE ... NOT NULL fail (or silently be skipped) on some databases.
	 *
	 * @param IOutput $output
	 * @param \Closure $schemaClosure
	 * @param array $options
	 * @return void
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$this->output = $output;
		$this->logInfo('Prepare migration');

		// Let the tableManager use its own live schema (not the target schema from
		// $schemaClosure, which may not reflect the actual current DB state yet)
		$this->tableManager->createSchema();

		// remove duplicate shares (and other unique-constrained duplicates) first,
		// while poll_id/group_id might still be nullish - NULL never blocks a
		// duplicate insert, so this has to run before values get normalized to 0
		$message = $this->tableManager->deleteAllDuplicates();
		$this->logInfo($message, 'preMigration:  ');

		// fix nullish values in poll_id and group_id and set 0 in case of null
		$message = $this->tableManager->fixNullishShares();
		$this->logInfo($message, 'preMigration:  ');

		$message = $this->tableManager->fixNullishPollGroupRelations();
		$this->logInfo($message, 'preMigration:  ');
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
		$this->output = $output;
		$this->schema = $schemaClosure();
		$this->tableManager->setConnection($this->connection);
		$this->tableManager->setSchema($this->schema);

		$message = $this->tableManager->createTables();
		$this->logInfo($message, 'runMigration:  ');

		if (!($this->schema instanceof ISchemaWrapper)) {
			return null;
		}

		return $this->schema;
	}

	/**
	 * Logs the given message to the output.
	 *
	 * @param string|array $message The message to log, can be a string or an array of strings.
	 * @param string $prefix Optional prefix for the message, defaults to an empty string.
	 * @return void
	 */
	private function logInfo(string|array $message, string $prefix = ''): void {
		if ($this->output) {
			if (is_array($message)) {
				foreach ($message as $msg) {
					$this->output->info($prefix . 'Polls - ' . $msg);
				}
			} else {
				$this->output->info($prefix . 'Polls - ' . $message);
			}
		}
	}

}
