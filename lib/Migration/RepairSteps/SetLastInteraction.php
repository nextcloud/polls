<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Migration\RepairSteps;

use OCA\Polls\Db\V4\TableManager;
use OCA\Polls\Db\WatchMapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class SetLastInteraction implements IRepairStep {
	public function __construct(
		private TableManager $tableManager,
		private IDBConnection $connection,
		private WatchMapper $watchMapper,
	) {
	}

	public function getName() {
		return 'Polls - Fix last interaction of poll';
	}

	public function run(IOutput $output): void {
		$message = $this->tableManager->setLastInteraction();
		$output->info($message);
	}
}
