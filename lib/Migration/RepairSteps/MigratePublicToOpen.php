<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Migration\RepairSteps;

use OCA\Polls\Db\V4\TableManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MigratePublicToOpen implements IRepairStep {
	public function __construct(
		private TableManager $tableManager,
		private IDBConnection $connection,
	) {
	}

	public function getName() {
		return 'Polls - Migrate access values from public to open';
	}

	public function run(IOutput $output): void {
		$this->tableManager->setConnection($this->connection);

		$messages = $this->tableManager->migratePublicToOpen();
		foreach ($messages as $message) {
			$output->info($message);
		}

	}
}
