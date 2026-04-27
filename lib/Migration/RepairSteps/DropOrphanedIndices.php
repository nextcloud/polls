<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Migration\RepairSteps;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\V9\IndexManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class DropOrphanedIndices implements IRepairStep {
	public function __construct(
		private IndexManager $indexManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
	}

	public function getName(): string {
		return 'Polls - Drop obsolete indices';
	}

	public function run(IOutput $output): void {
		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);
		$messages = $this->indexManager->removeObsoleteIndices();
		$this->connection->migrateToSchema($this->schema);

		foreach ($messages as $message) {
			$output->info($message);
		}
	}
}
