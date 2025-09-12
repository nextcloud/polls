<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration\RepairSteps;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\V4\TableManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * remove old migration entries from versions prior to polls 3.x
 * including migration versions from test releases
 * theoretically, only this migration should be existent. If not, no matter
 *
 */
class RemoveObsoleteMigrations implements IRepairStep {
	public function __construct(
		private TableManager $tableManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
	}

	/*
	 * @inheritdoc
	 */
	public function getName() {
		return 'Polls - Remove old migrations from migrations table';
	}

	/*
	 * @inheritdoc
	 */
	public function run(IOutput $output): void {
		$this->tableManager->setConnection($this->connection);

		$messages = $this->tableManager->removeObsoleteMigrations();
		foreach ($messages as $message) {
			$output->info($message);
		}

	}
}
