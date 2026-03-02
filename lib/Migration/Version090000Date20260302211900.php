<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration;

use OCA\Polls\Db\V6\IndexManager;
use OCA\Polls\Db\V6\TableManager;
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
class Version090000Date20260302211900 extends SimpleMigrationStep {
	private ISchemaWrapper $schema;
	private ?IOutput $output = null;

	public function __construct(
		private TableManager $tableManager,
		private IDBConnection $connection,
	) {
	}

	public function name(): string {
		return 'Polls migration to version 9.0.0';
	}

	public function description(): string {
		return 'Migrates Polls\' tables to the current schema';
	}

	/**
	 * This method is called before the schema change.
	 *
	 * @param IOutput $output
	 * @param \Closure $schemaClosure
	 * @param array $options
	 * @return void
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$this->output = $output;
		$this->logInfo('Prepare migration- no operation needed');
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
	 * This method is called after the schema change.
	 * It is used to perform any post-migration steps, such as migrating options to a hash.
	 * Main steps:
	 * 1. Ensure that option hashes are created correctly for options and votes
	 *
	 * @param IOutput $output
	 * @param \Closure $schemaClosure
	 * @param array $options
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$this->output = $output;
		$this->logInfo('Post migration steps- no operation needed');

		// Clean up tables before creating indices and foreign keys

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
