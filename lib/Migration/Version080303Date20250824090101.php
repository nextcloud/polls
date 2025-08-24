<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\V2\IndexManager;
use OCA\Polls\Db\V2\TableManager;
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
class Version080303Date20250824090101 extends SimpleMigrationStep {
	private ISchemaWrapper $schema;
	private ?IOutput $output = null;

	public function __construct(
		private TableManager $tableManager,
		private IndexManager $indexManager,
		private IDBConnection $connection,
	) {
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

	/**
	 * This method is called before the schema change.
	 * All the existing calls are necessary to prepare the database for the migration.
	 * Main steps:
	 * 1. Make sure that no nullish values are used for poll_id and group_id in the share table
	 * 2. Remove all orphaned records which have no relation to a poll group (shares) or a poll (all)
	 * 3. Remove all duplicate records based on unique index definition
	 * 4. Tidy the watch table by removing all entries which are older than now
	 *
	 * @param IOutput $output
	 * @param \Closure $schemaClosure
	 * @param array $options
	 * @return void
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		$this->output = $output;
		$this->logInfo('Prepare migration');

		// remove foreign keys and unique indices from the share table in preparation of fixing nullish values
		$this->indexManager->createSchema();        // Let the indexManager use it's own schema
		$message = $this->indexManager->removeUniqueIndicesFromTable(Share::TABLE);
		$this->logInfo($message, 'preMigration:  ');
		$message = $this->indexManager->removeForeignKeysFromTable(Share::TABLE);
		$this->logInfo($message, 'preMigration:  ');
		$this->indexManager->migrateToSchema();

		// fix nullish values in poll_id and group_id and set 0 in case of null
		$message = $this->tableManager->fixNullishShares();
		$this->logInfo($message, 'preMigration:  ');

		// remove all orphaned records
		$message = $this->tableManager->removeOrphaned();
		$this->logInfo($message, 'preMigration:  ');

		// remove all duplicates
		$this->tableManager->createSchema();
		$message = $this->tableManager->deleteAllDuplicates();
		$this->logInfo($message, 'preMigration:  ');
		$this->tableManager->migrateToSchema();

		$message = $this->tableManager->tidyWatchTable(time());
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
		$this->logInfo('Post migration steps');

		$this->tableManager->createSchema();
		$message = $this->tableManager->migrateOptionsToHash();
		$this->logInfo($message, 'postMigration: ');

		$message = $this->tableManager->removeObsoleteTables();
		$this->logInfo($message, 'postMigration: ');

		$this->tableManager->createSchema();
		$message = $this->tableManager->removeObsoleteColumns();
		$this->tableManager->migrateToSchema();
		$this->logInfo($message, 'postMigration: ');


		$this->indexManager->createSchema();

		$message = $this->indexManager->createForeignKeyConstraints();
		$this->logInfo($message, 'postMigration:  ');

		$message = $this->indexManager->createUniqueIndices();
		$this->logInfo($message, 'postMigration:  ');

		// skip creating optional indices and leave it to 'occ db:add-missing-indices'
		$this->indexManager->migrateToSchema();
	}
}
