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
use OCA\Polls\Db\IndexManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * @psalm-suppress UnusedClass
 */
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
		$messages = array_merge($messages, $this->indexManager->createIndices());

		$this->connection->migrateToSchema($this->schema);

		foreach ($messages as $message) {
			$output->info($message);
		}

		$output->info('Polls - Foreign key contraints created.');
		$output->info('Polls - Indices created.');
	}
}
