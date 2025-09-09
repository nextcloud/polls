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

class FixNullish implements IRepairStep {
	public function __construct(
		private IDBConnection $connection,
		private TableManager $tableManager,
	) {
	}

	public function getName() {
		return 'Polls - Fix nullish values where not allowed';
	}

	public function run(IOutput $output): void {
		$this->tableManager->setConnection($this->connection);

		$messages = $this->tableManager->fixNullishShares();
		foreach ($messages as $message) {
			$output->info('Polls - ' . $message);
		}

		$messages = $this->tableManager->fixNullishPollGroupRelations();
		foreach ($messages as $message) {
			$output->info('Polls - ' . $message);
		}

	}
}
