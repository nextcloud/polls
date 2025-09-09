<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Migration\RepairSteps;

use Doctrine\DBAL\Schema\Schema;
use Exception;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\V4\TableManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Preparation before migration
 * Remove all invalid records to avoid erros while adding indices ans constraints
 */
class CleanTables implements IRepairStep {
	public function __construct(
		private IDBConnection $connection,
		private TableManager $tableManager,
		private Schema $schema,
	) {
	}

	public function getName():string {
		return 'Polls - Delete duplicates and orphaned records';
	}

	public function run(IOutput $output):void {
		if ($this->connection->tableExists(Poll::TABLE)) {
			try {
				$this->tableManager->migrateOptionsToHash();
				$this->tableManager->removeOrphaned();
				$this->tableManager->deleteAllDuplicates();
				$this->tableManager->tidyWatchTable(time());
			} catch (Exception $e) {
				// Simply skip repair, if it breaks and rely on the next run
			}
		}
	}
}
