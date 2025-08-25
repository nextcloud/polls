<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Migration\RepairSteps;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\V3\TableManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class CreateTables implements IRepairStep {
	public function __construct(
		private TableManager $tableManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
	}

	public function getName() {
		return 'Polls - Create missing tables and columns';
	}

	public function run(IOutput $output): void {
		// drop watch table to get it recreated
		$messages = $this->tableManager->removeWatch();
		foreach ($messages as $message) {
			$output->info($message);
		}
		// secure, that the schema is updated to the current status
		$this->schema = $this->connection->createSchema();
		$this->tableManager->setSchema($this->schema);

		$messages = $this->tableManager->createTables();

		$this->connection->migrateToSchema($this->schema);

		foreach ($messages as $message) {
			$output->info($message);
		}
	}
}
