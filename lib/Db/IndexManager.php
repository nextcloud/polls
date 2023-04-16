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


namespace OCA\Polls\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Migration\TableSchema;
use OCP\IConfig;
use OCP\IDBConnection;

class IndexManager {
	private Schema $schema;
	private string $dbPrefix;
	
	public function __construct(
		private IConfig $config,
		protected IDBConnection $connection
	) {
		$this->schema = $this->connection->createSchema();
		$this->dbPrefix = $this->config->getSystemValue('dbtableprefix', 'oc_');
	}

	/**
	 * execute the migration
	 */
	public function migrate(): void {
		$this->connection->migrateToSchema($this->schema);
	}

	public function refreshSchema(): void {
		$this->schema = $this->connection->createSchema();
	}

	/**
	 * Create all indices
	 */
	public function createIndices(): array {
		$messages = [];

		foreach (TableSchema::UNIQUE_INDICES as $tableName => $values) {
			$messages[] = $this->createIndex($tableName, $values['name'], $values['columns'], $values['unique']);
		}

		return $messages;
	}

	/**
	 * add on delete fk contraints to all tables referencing the main polls table
	 */
	public function createForeignKeyConstraints(): array {
		$messages = [];
		
		foreach (TableSchema::FK_CHILD_TABLES as $childTable) {
			$messages[] = $this->createForeignKeyConstraint(TableSchema::FK_PARENT_TABLE, $childTable);
		}
		
		return $messages;
	}

	/**
	 * add an on delete fk contraint
	 */
	public function createForeignKeyConstraint(string $parentTableName, string $childTableName): string {
		$parentTableName = $this->dbPrefix . $parentTableName;
		$childTableName = $this->dbPrefix . $childTableName;
		$parentTable = $this->schema->getTable($parentTableName);
		$childTable = $this->schema->getTable($childTableName);

		$childTable->addForeignKeyConstraint($parentTable, ['poll_id'], ['id'], ['onDelete' => 'CASCADE']);
		return 'Added ' . $parentTableName . '[\'poll_id\'] <- ' . $childTableName . '[\'id\']';
	}

	/**
	 * Create index
	 */
	public function createIndex(string $tableName, string $indexName, array $columns, bool $unique = false): string {
		$tableName = $this->dbPrefix . $tableName;
		if ($this->schema->hasTable($tableName)) {
			$table = $this->schema->getTable($tableName);
			if (!$table->hasIndex($indexName)) {
				if ($unique) {
					$table->addUniqueIndex($columns, $indexName);
					return 'Added unique index ' . $indexName . ' for ' . json_encode($columns) . ' to ' . $tableName;
				} else {
					$table->addIndex($columns, $indexName);
					return 'Added index ' . $indexName . ' to ' . $tableName;
				}
			}
			return 'Unique index ' . $indexName . ' already exists in ' . $tableName;
		}
		return 'Table ' . $tableName . ' does not exist';
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
	public function removeForeignKeysFromTable(string $tableName): array {
		$messages = [];
		$tableName = $this->dbPrefix . $tableName;
		if ($this->schema->hasTable($tableName)) {
			$table = $this->schema->getTable($tableName);
			foreach ($table->getForeignKeys() as $foreignKey) {
				$table->removeForeignKey($foreignKey->getName());
				$messages[] = 'Removed ' . $foreignKey->getName() . ' from ' . $tableName;
			}
		}

		return $messages;
	}

	/**
	 * remove all UNIQUE indices from $table
	 */
	public function removeUniqueIndicesFromTable(string $tableName): array {
		$messages = [];
		$tableName = $this->dbPrefix . $tableName;
		if ($this->schema->hasTable($tableName)) {
			$table = $this->schema->getTable($tableName);
			foreach ($table->getIndexes() as $index) {
				if (strpos($index->getName(), 'UNIQ_') === 0) {
					$table->dropIndex($index->getName());
					$messages[] = 'Removed ' . $index->getName() . ' from ' . $tableName;
				}
			}
		}
		return $messages;
	}

	/**
	 * remove all UNIQUE indices from $table
	 */
	public function removeGenericIndicesFromTable(string $tableName): array {
		$messages = [];
		$tableName = $this->dbPrefix . $tableName;
		if ($this->schema->hasTable($tableName)) {
			$table = $this->schema->getTable($tableName);
			foreach ($table->getIndexes() as $index) {
				if (strpos($index->getName(), 'IDX_') === 0) {
					$table->dropIndex($index->getName());
					$messages[] = 'Removes ' . $index->getName() . ' from ' . $tableName;
				}
			}
		}
		return $messages;
	}
}
