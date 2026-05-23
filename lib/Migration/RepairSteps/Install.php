<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Migration\RepairSteps;

use OCA\Polls\Db\V10\IndexManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Install implements IRepairStep {
	public function __construct(
		private IndexManager $indexManager,
		private IDBConnection $connection,
	) {
	}

	public function getName() {
		return 'Polls - Install';
	}

	public function run(IOutput $output): void {
		$schema = $this->connection->createSchema();
		$this->indexManager->setSchema($schema);

		$messages = $this->indexManager->createForeignKeyConstraints();
		$messages = array_merge($messages, $this->indexManager->createUniqueIndices());
		$messages = array_merge($messages, $this->indexManager->createOptionalIndices());

		$this->connection->migrateToSchema($schema);

		foreach ($messages as $message) {
			$output->info($message);
		}

		$output->info('Polls - Indices created.');
	}
}
