<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Migration\RepairSteps;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\V8\IndexManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CreateUniqueIndices implements IRepairStep {
	public function __construct(
		private IndexManager $indexManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
	}

	public function getName() {
		return 'Polls - Create all unique indices';
	}

	public function run(IOutput $output): void {
		$messages = [];

		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);

		$messages = array_merge($messages, $this->indexManager->createUniqueIndices());

		try {
			$this->connection->migrateToSchema($this->schema);
		} catch (\Exception $e) {
			// Hard fallback!
			// Recreating indices can affect system performance on some db engines with large datasets.
			// But the app relies on these indices to function properly, so we have to ensure they are created.
			// If for any reasons the unique indices cannot be created, we remove all unique indices and create them again.
			// This is a workaround for index conflicts that might occur during migration.
			if (str_contains($e->getMessage(), 'already exists') || str_contains($e->getMessage(), '42P07')) {
				$output->warning('Polls - Index conflict detected, rebuilding unique indices: ' . $e->getMessage());
				$this->schema = $this->connection->createSchema();
				$this->indexManager->setSchema($this->schema);
				$messages = array_merge($messages, $this->indexManager->repairPrimaryKeys());
				$messages = array_merge($messages, $this->indexManager->removeAllUniqueIndices());
				$this->connection->migrateToSchema($this->schema);

				$this->schema = $this->connection->createSchema();
				$this->indexManager->setSchema($this->schema);
				$messages = array_merge($messages, $this->indexManager->createUniqueIndices());
				$this->connection->migrateToSchema($this->schema);
			} else {
				throw $e;
			}
		}

		foreach ($messages as $message) {
			$output->info($message);
		}

		$output->info('Polls - Indices created.');
	}
}
