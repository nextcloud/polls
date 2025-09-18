<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\V4\TableManager;
use OCA\Polls\Db\V4\IndexManager;
use OCA\Polls\Command\Command;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class Rebuild extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:rebuild';
	protected string $description = 'Rebuilds poll\'s table structure';
	protected array $operationHints = [
		'All polls tables will get checked and eventually updated against the current schema.',
		'*****************************',
		'**    Please understand    **',
		'*****************************',
		'The process will also remove all optional indices.',
		'This can lead to a database performance impact on the app after the recreation is done.',
		'',
		'To recreate the optional indices, run the command \'occ db:add-missing-indices\'',
		'Note: NO data migration will be executed, so make sure you have a backup of your database.',
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
		$this->connection->migrateToSchema($this->schema);

		$this->printComment('Step 2. Tidy records before rebuilding the schema');
		$this->fixNullish();
		$this->migrateShareLabels();

		$this->printComment('Step 3. Create or update tables to current shema');
		$this->createOrUpdateSchema();
		$this->connection->migrateToSchema($this->schema);

		$this->printComment('Step 4. Remove orphaned tables and columns');
		$this->dropObsoleteTables();
		$this->dropObsoleteColumns();
		$this->connection->migrateToSchema($this->schema);

		$this->printComment('Step 5. Validate and fix records');
		$this->removeOrphaned();
		$this->updateHashes();
		$this->deleteAllDuplicates();
		$this->setLastInteraction();

		$this->printComment('Step 6. Recreate unique indices and foreign key constraints');
		$this->addForeignKeyConstraints();
		$this->addUniqueIndices();
		$this->connection->migrateToSchema($this->schema);

		$this->printComment('Rebuild finished. The database structure is now up to date.');
		$this->printComment('Execute \'occ db:add-missing-indices\' to add missing optional indices');

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

	private function fixNullish(): void {
		$this->printComment(' - Fix nullish values');
		$messages = $this->tableManager->fixNullishShares();
		$this->printInfo($messages, '   ');

		$messages = $this->tableManager->fixNullishPollGroupRelations();
		$this->printInfo($messages, '   ');
	}

	private function migrateShareLabels(): void {
		$this->printComment(' - migrate share labels to displayname for public shares');
		$messages = $this->tableManager->migrateShareLabels();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Create index for $table
	 */
	private function addUniqueIndices(): void {
		$this->printComment(' - Add unique indices');
		$messages = $this->indexManager->createUniqueIndices();
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
	private function updateHashes(): void {
		$this->printComment(' - Add or update hashes');
		$messages = $this->tableManager->updateHashes();
		$this->printInfo($messages, '   ');
	}

	private function dropObsoleteColumns(): void {
		$this->printComment(' - Drop orphaned columns');
		$messages = $this->tableManager->removeObsoleteColumns();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Remove obsolete tables if they still exist
	 */
	private function dropObsoleteTables(): void {
		$this->printComment(' - Drop orphaned tables');
		$messages = $this->tableManager->removeObsoleteTables();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Initialize last poll interactions timestamps
	 */
	public function setLastInteraction(): void {
		$messages = $this->tableManager->setLastInteraction();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Remove obsolete tables if they still exist
	 */
	private function deleteAllDuplicates(): void {
		$this->printComment(' - Remove duplicates');
		$messages = $this->tableManager->deleteAllDuplicates();
		$this->printInfo($messages, '   ');
	}

	/**
	 * Remove obsolete tables if they still exist
	 */
	private function removeOrphaned(): void {
		$this->printComment(' - Remove orphaned records');
		$messages = $this->tableManager->removeOrphaned();
		foreach ($messages as $message) {
			$this->printInfo("    $message");
		}
	}

	/**
	 * remove on delete fk constraints from all tables referencing the main polls table
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
		$this->printComment(' - Remove optional indices');
		$messages = $this->indexManager->removeNamedIndices();
		$this->printInfo($messages, ' - ');
	}

}
