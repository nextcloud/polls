<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration\RepairSteps;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\IndexManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Preparation before migration
 * Remove all indices and foreign key constraints to avoid errors
 * while changing the schema
 *
 */
class RemoveIndices implements IRepairStep {
	public function __construct(
		private IndexManager $indexManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
	}

	/*
	 * @inheritdoc
	 */
	public function getName() {
		return 'Polls - Remove foreign key constraints and generic indices';
	}

	/*
	 * @inheritdoc
	 */
	public function run(IOutput $output): void {
		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);

		$messages = $this->indexManager->removeAllForeignKeyConstraints();
		foreach ($messages as $message) {
			$output->info($message);
		}

		$messages = $this->indexManager->removeAllUniqueIndices();
		foreach ($messages as $message) {
			$output->info($message);
		}

		$this->connection->migrateToSchema($this->schema);
	}
}
