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

use Doctrine\DBAL\Schema\Exception\IndexDoesNotExist;
use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Migration\TableSchema;
use OCP\IConfig;
use OCP\IDBConnection;

class IndexManager {
	
	private string $dbPrefix;
	
	public function __construct(
		private IConfig $config,
		private IDBConnection $connection,
		private Schema $schema,
	) {
		$this->dbPrefix = $this->config->getSystemValue('dbtableprefix', 'oc_');
	}

	public function setSchema(Schema &$schema): void {
		$this->schema = $schema;
	}

	/**
	 * Create all indices
	 */
	public function createIndices(): array {
		$messages = [];

		foreach (TableSchema::UNIQUE_INDICES as $tableName => $values) {
			$messages[] = $this->createIndex($tableName, $values['name'], $values['columns'], $values['unique']);
		}

		foreach (TableSchema::COMMON_INDICES as $index) {
			$messages[] = $this->createIndex($index['table'], $index['name'], $index['columns'], $index['unique']);
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
					return 'Added index ' . $indexName . ' for ' . json_encode($columns) . ' to ' . $tableName;
				}
			}
			return 'Index ' . $indexName . ' already exists in ' . $tableName;
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
	 * remove all generic indices
	 *
	 * @return string[] logged messages
	 */
	public function removeNamedIndices(): array {
		$messages = [];

		foreach (TableSchema::COMMON_INDICES as $index) {
			$message = $this->removeNamedIndexFromTable($index['table'], $index['name']);
			if ($message) {
				$messages[] = $message;
			}
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

	/**
	 * remove all generic indices from $table
	 *
	 * @param string $tableName table name of table to remove the index from
	 * @param string $indexName name of index to remove
	 *
	 * @return null|string
	 */
	public function removeNamedIndexFromTable(string $tableName, string $indexName): string|null {
		$tableName = $this->dbPrefix . $tableName;

		try {
			if ($this->schema->hasTable($tableName)) {
				$table = $this->schema->getTable($tableName);
				$table->dropIndex($indexName);
				$message = 'Removed ' . $indexName . ' from ' . $tableName;
			}
		} catch (IndexDoesNotExist $e) {
			// common index does not exist, skip it
			$message = null;
		}
		return $message;
	}
}
