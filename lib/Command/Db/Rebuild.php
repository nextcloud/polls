<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\TableManager;
use OCA\Polls\Db\IndexManager;
use OCA\Polls\Command\Command;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class Rebuild extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:rebuild';
	protected string $description = 'Rebuilds poll\'s table structure';
	protected array $operationHints = [
		'All polls tables will get checked against the current schema.',
		'NO data migration will be executed, so make sure you have a backup of your database.',
		'',
		'*****************************',
		'**    Please understand    **',
		'*****************************',
		'The process will also recreate all indices and foreign key constraints.',
		'This can lead to a database performance impact on the app after the recreation is done.',
	];

	public function __construct(
		private TableManager $tableManager,
		private IndexManager $indexManager,
		private IDBConnection $connection,
		private Schema $schema,

	) {
		parent::__construct();
	}

	protected function runCommands(): int {
		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);
		$this->tableManager->setSchema($this->schema);

		$this->printComment('Step 1. Remove all indices and foreign key constraints');
		$this->deleteForeignKeyConstraints();
		$this->deleteGenericIndices();
		$this->deleteUniqueIndices();
		$this->deleteNamedIndices();

		$this->printComment('Step 2. Remove all orphaned tables and columns');
		$this->removeObsoleteTables();
		$this->removeObsoleteColumns();

		$this->connection->migrateToSchema($this->schema);

		$this->printComment('Step 3. Create or update tables to current shema');
		$this->createOrUpdateSchema();

		$this->connection->migrateToSchema($this->schema);

		$this->printComment('Step 4. set hashes for votes and options');
		$this->migrateOptionsToHash();

		$this->printComment('Step 5. Remove invalid records (orphaned and duplicates)');
		$this->cleanTables();

		$this->printComment('Step 6. Recreate indices and foreign key constraints');
		$this->addForeignKeyConstraints();
		$this->addIndices();

		$this->connection->migrateToSchema($this->schema);

		return 0;
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function addForeignKeyConstraints(): void {
		$this->printComment(' - Add foreign key constraints');
		$messages = $this->indexManager->createForeignKeyConstraints();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Create index for $table
	 */
	private function addIndices(): void {
		$this->printComment(' - Add indices');
		$messages = $this->indexManager->createIndices();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Iterate over tables and make sure, the are created or updated
	 * according to the schema
	 */
	private function createOrUpdateSchema(): void {
		$this->printComment(' - Set db structure');
		$messages = $this->tableManager->createTables();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Add or update hash for votes and options
	 */
	private function migrateOptionsToHash(): void {
		$this->printComment(' - Add or update hashes');
		$messages = $this->tableManager->migrateOptionsToHash();
		$this->printInfo($messages, '   ');
	}

	private function removeObsoleteColumns(): void {
		$this->printComment(' - Drop orphaned columns');
		$messages = $this->tableManager->removeObsoleteColumns();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Remove obsolete tables if they still exist
	 */
	private function removeObsoleteTables(): void {
		$this->printComment(' - Drop orphaned tables');
		$messages = $this->tableManager->removeObsoleteTables();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Initialize last poll interactions timestamps
	 */
	public function resetLastInteraction(): void {
		$messages = $this->tableManager->resetLastInteraction();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Remove obsolete tables if they still exist
	 */
	private function cleanTables(): void {
		$this->printComment(' - Remove orphaned records');
		$orphaned = $this->tableManager->removeOrphaned();
		foreach ($orphaned as $table => $count) {
			$this->printInfo("    Removed $count orphaned records from $table");
		}

		$this->printComment(' - Remove duplicates');
		$messages = $this->tableManager->deleteAllDuplicates();
		$this->printInfo($messages, '   ');
	}

	/**
	 * remove on delete fk contraint from all tables referencing the main polls table
	 */
	private function deleteForeignKeyConstraints(): void {
		$this->printComment(' - Remove foreign key constraints');
		$messages = $this->indexManager->removeAllForeignKeyConstraints();
		$this->printInfo($messages, '   ');
	}

	/**
	 * remove all generic indices
	 */
	private function deleteGenericIndices(): void {
		$this->printComment(' - Remove generic indices');
		$messages = $this->indexManager->removeAllGenericIndices();
		$this->printInfo($messages, '   ');
	}

	/**
	 * remove all unique indices
	 */
	private function deleteUniqueIndices(): void {
		$this->printComment(' - Remove unique indices');
		$messages = $this->indexManager->removeAllUniqueIndices();
		$this->printInfo($messages, '   ');
	}

	/**
	 * remove all named indices
	 */
	private function deleteNamedIndices(): void {
		$this->printComment(' - Remove common indices');
		$messages = $this->indexManager->removeNamedIndices();
		$this->printInfo($messages, ' - ');
	}

}
