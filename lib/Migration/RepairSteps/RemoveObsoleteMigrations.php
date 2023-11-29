<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Migration\RepairSteps;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\TableManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * remove old migration entries from versions prior to polls 3.x
 * including migration versions from test releases
 * theoretically, only this migration should be existent. If not, no matter
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
