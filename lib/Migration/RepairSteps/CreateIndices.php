<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Migration\RepairSteps;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\V5\IndexManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CreateIndices implements IRepairStep {
	public function __construct(
		private IndexManager $indexManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
	}

	public function getName() {
		return 'Polls - Create all unique and optional indices and foreign key constraints';
	}

	public function run(IOutput $output): void {
		$messages = [];
		// secure, that the schema is updated to the current status
		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);

		// remove foreign keys from the share table
		// cannot be used anymore since v8.0.0
		$messages = array_merge($messages, $this->indexManager->removeForeignKeysFromTable(Share::TABLE));

		$messages = array_merge($messages, $this->indexManager->createForeignKeyConstraints());
		$messages = array_merge($messages, $this->indexManager->createUniqueIndices());
		$messages = array_merge($messages, $this->indexManager->createOptionalIndices());
		$this->connection->migrateToSchema($this->schema);

		foreach ($messages as $message) {
			$output->info($message);
		}

		$output->info('Polls - Foreign key contraints created.');
		$output->info('Polls - Indices created.');
	}
}
