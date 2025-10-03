<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Migration\RepairSteps;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\V5\IndexManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Install implements IRepairStep {
	public function __construct(
		private IndexManager $indexManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
	}

	public function getName() {
		return 'Polls - Install';
	}

	public function run(IOutput $output): void {
		$messages = [];
		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);

		$messages = array_merge($messages, $this->indexManager->createForeignKeyConstraints());
		$messages = array_merge($messages, $this->indexManager->createUniqueIndices());

		$this->connection->migrateToSchema($this->schema);

		foreach ($messages as $message) {
			$output->info($message);
		}

		$output->info('Polls - Foreign key contraints created.');
		$output->info('Polls - Indices created.');
	}
}
