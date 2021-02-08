<?php
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

namespace OCA\Polls\Migration;

use OC\DB\SchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

class RemoveIndices implements IRepairStep {
	/** @var IDBConnection */
	private $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	public function getName() {
		return 'Remove polls table indices';
	}

	public function run(IOutput $output) {
		$this->removeUniqueIndices('polls_options');
		$this->removeUniqueIndices('polls_log');
		$this->removeUniqueIndices('polls_notif');
		$this->removeUniqueIndices('polls_share');
		$this->removeUniqueIndices('polls_votes');
		$this->removeUniqueIndices('polls_preferences');
		$this->removeUniqueIndices('polls_preferences');
	}

	/**
	 * remove an index with $indexName from $table
	 */
	private function removeIndex(string $tableName, string $indexName) {
		$schema = new SchemaWrapper($this->connection);
		if ($schema->hasTable($tableName)) {
			$table = $schema->getTable($tableName);
			if ($table->hasIndex($indexName)) {
				$table->dropIndex($indexName);
				$this->connection->migrateToSchema($schema->getWrappedSchema());
			}
		}
	}

	/**
	 * remove all UNIQUE indices from $table
	 */
	private function removeUniqueIndices(string $tableName) {
		$schema = new SchemaWrapper($this->connection);
		if ($schema->hasTable($tableName)) {
			$table = $schema->getTable($tableName);
			foreach ($table->getIndexes() as $index) {
				if (strpos($index->getName(), 'UNIQ_') === 0) {
					$this->removeIndex($tableName, $index->getName());
				}
			}
		}
	}
}
