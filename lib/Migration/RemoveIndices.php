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

use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\Migration\IRepairStep;
use OCP\Migration\IOutput;

/**
 * Preparation before migration
 * Remove all indices and foreign key constraints to avoid errors
 * while changing the schema
 */
class RemoveIndices implements IRepairStep {
	/** @var Connection */
	private $connection;

	/** @var SchemaWrapper */
	private $schema;

	public function __construct(Connection $connection) {
		$this->connection = $connection;
		$this->schema = new SchemaWrapper($this->connection);
	}

	public function getName() {
		return 'Polls - Remove foreign key constraints and generic indices';
	}

	public function run(IOutput $output): void {
		$this->removeAllForeignKeyConstraints();
		$this->removeAllGenericIndices();
		$this->removeAllUniqueIndices();
		$this->migrate();
	}

	/**
	 * execute the migration
	 */
	public function migrate() {
		$this->connection->migrateToSchema($this->schema->getWrappedSchema());
	}

	/**
	 * 	remove all foreign keys from $tableName
	 */
	public function removeAllForeignKeyConstraints(): array {
		$messages = [];

		foreach (TableSchema::FK_CHILD_TABLES as $tableName) {
			$messages = array_merge($messages, $this->removeForeignKeysFromTable($tableName));
		}

		return $messages;
	}

	/**
	 * 	remove all foreign keys from $tableName
	 */
	public function removeAllGenericIndices(): array {
		$messages = [];

		foreach (TableSchema::FK_CHILD_TABLES as $tableName) {
			$messages = array_merge($messages, $this->removeGenericIndicesFromTable($tableName));
		}

		return $messages;
	}

	/**
	 * 	remove all foreign keys from $tableName
	 */
	public function removeAllUniqueIndices(): array {
		$messages = [];

		foreach (TableSchema::UNIQUE_INDICES as $tableName => $value) {
			$messages = array_merge($messages, $this->removeUniqueIndicesFromTable($tableName));
		}

		return $messages;
	}

	/**
	 * 	remove all foreign keys from $tableName
	 */
	private function removeForeignKeysFromTable(string $tableName): array {
		$messages = [];
		if ($this->schema->hasTable($tableName)) {
			$table = $this->schema->getTable($tableName);
			foreach ($table->getForeignKeys() as $foreignKey) {
				$messages[] = 'Remove ' . $foreignKey->getName() . ' from ' . $tableName;
				$table->removeForeignKey($foreignKey->getName());
			}
		}
		
		return $messages;
	}
	
	/**
	 * remove all UNIQUE indices from $table
	 */
	private function removeUniqueIndicesFromTable(string $tableName): array {
		$messages = [];
		if ($this->schema->hasTable($tableName)) {
			$table = $this->schema->getTable($tableName);
			foreach ($table->getIndexes() as $index) {
				if (strpos($index->getName(), 'UNIQ_') === 0) {
					$messages[] = 'Remove ' . $index->getName() . ' from ' . $tableName;
					$table->dropIndex($index->getName());
				}
			}
		}
		return $messages;
	}
	
	/**
	 * remove all UNIQUE indices from $table
	 */
	private function removeGenericIndicesFromTable(string $tableName): array {
		$messages = [];
		if ($this->schema->hasTable($tableName)) {
			$table = $this->schema->getTable($tableName);
			foreach ($table->getIndexes() as $index) {
				if (strpos($index->getName(), 'IDX_') === 0) {
					$messages[] = 'Remove ' . $index->getName() . ' from ' . $tableName;
					$table->dropIndex($index->getName());
				}
			}
		}
		return $messages;
	}
}
