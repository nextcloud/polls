<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db\V8;

use Doctrine\DBAL\Schema\Exception\IndexDoesNotExist;
use Exception;
use OCA\Polls\Migration\V8\TableSchema;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/** @psalm-suppress UnusedClass */
class IndexManager extends DbManager {

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		protected IConfig $config,
		protected IDBConnection $connection,
		protected LoggerInterface $logger,
	) {
		parent::__construct($config, $connection, $logger);
	}

	/**
	 * Check if two column lists match, ignoring order.
	 * @param string[] $existing list of columns in existing index
	 * @param string[] $expected list of columns in expected index definition
	 * @return bool true if the lists match, false otherwise
	 */
	private function columnsMatch(array $existing, array $expected): bool {
		sort($existing);
		sort($expected);
		return $existing === $expected;
	}

	/**
	 * Create optional indices
	 * Usually they should be created by the AddMissingIndicesListener
	 * or on first time installation of polls.
	 *
	 * @return string[] logged messages
	 */
	public function createOptionalIndices(): array {
		$messages = [];

		foreach (TableSchema::OPTIONAL_INDICES as $table => $indices) {
			foreach ($indices as $name => $definition) {
				$messages[] = $this->createIndex($table, $name, $definition['columns']);
			}
		}

		return $messages;
	}

	/**
	 * Ensure all tables have their primary key on 'id'.
	 * All PKs in this app are autoincrement 'id' columns — if one is missing
	 * (e.g. after a failed migration on a non-transactional DB engine), restore it.
	 *
	 * @return string[] logged messages
	 */
	public function repairPrimaryKeys(): array {
		$this->needsSchema();
		$messages = [];

		foreach (array_keys(TableSchema::TABLES) as $tableName) {
			$prefixedTable = $this->getTableName($tableName);

			if (!$this->schema->hasTable($prefixedTable)) {
				continue;
			}

			$table = $this->schema->getTable($prefixedTable);

			if ($table->getPrimaryKey() === null) {
				$table->setPrimaryKey(['id']);
				$messages[] = 'Restored missing primary key for ' . $tableName;
			}
		}

		if (empty($messages)) {
			$messages[] = 'All primary keys intact';
		}

		return $messages;
	}

	/**
	 * add 'on delete' fk contraints to all tables referencing the main polls table
	 * Foreign key constraints are crucial for the correct operation of the polls app.
	 * This for they have to be updated on every update.
	 *
	 * @return string[] logged messages
	 */
	public function createForeignKeyConstraints(): array {
		$messages = [];

		foreach (TableSchema::FK_INDICES as $parent => $child) {
			foreach ($child as $table => $childTable) {
				$messages[] = $this->createForeignKeyConstraint($parent, $table, $childTable['constraintColumn']);
			}
		}

		return $messages;
	}

	/**
	 * add one 'on delete' fk contraint
	 *
	 * @param string $parentTableName name of referred table
	 * @param string $childTableName name of referring table
	 * @return string log message
	 */
	private function createForeignKeyConstraint(string $parentTableName, string $childTableName, string $constraintColumn): string {
		$this->needsSchema();
		$parentTableName = $this->getTableName($parentTableName);
		$childTableName = $this->getTableName($childTableName);

		$parentTable = $this->schema->getTable($parentTableName);
		$childTable = $this->schema->getTable($childTableName);

		foreach ($childTable->getForeignKeys() as $fk) {
			if ($fk->getForeignTableName() === $parentTableName
				&& $fk->getLocalColumns() === [$constraintColumn]
				&& $fk->getForeignColumns() === ['id']
			) {
				return 'Foreign key ' . $childTableName . '[' . $constraintColumn . '] -> ' . $parentTableName . '[id] already exists';
			}
		}

		$childTable->addForeignKeyConstraint($parentTable, [$constraintColumn], ['id'], ['onDelete' => 'CASCADE']);
		return 'Added ' . $parentTableName . '[' . $constraintColumn . '] <- ' . $childTableName . '[id]';
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return list{0?: string,...}
	 */
	public function listExistingIndices(): array {
		$this->needsSchema();
		$messages = [];

		foreach (array_keys(TableSchema::TABLES) as $tableName) {
			$tableName = $this->getTableName($tableName);

			if ($this->schema->hasTable($tableName)) {
				$table = $this->schema->getTable($tableName);

				foreach ($table->getIndexes() as $index) {
					$messages[] = $tableName . ' - ' . $index->getName() . ' (' . implode(',', $index->getColumns()) . ')';
				}
			}
		}
		return $messages;
	}

	/**
	 * remove all foreign keys
	 *
	 * @return string[] logged messages
	 */
	public function removeAllForeignKeyConstraints(): array {
		$messages = [];

		foreach (TableSchema::FK_INDICES as $child) {
			foreach (array_keys($child) as $table) {
				$messages = array_merge($messages, $this->removeForeignKeysFromTable($table));
			}
		}

		return $messages;
	}

	/**
	 * remove all generic indices
	 *
	 * @return string[] logged messages
	 */
	public function removeAllGenericIndices(): array {
		$messages = [];

		foreach (array_keys(TableSchema::TABLES) as $table) {
			$messages = array_merge($messages, $this->removeGenericIndicesFromTable($table));
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

		foreach (TableSchema::OPTIONAL_INDICES as $table => $indices) {
			foreach (array_keys($indices) as $name) {
				$message = $this->removeNamedIndexFromTable($table, $name);
				if ($message !== null && $message !== '') {
					$messages[] = $message;
				}
			}
		}

		return $messages;
	}

	/**
	 * Remove indices listed in TableSchema::GONE_INDICES
	 *
	 * @return string[] logged messages
	 */
	public function removeObsoleteIndices(): array {
		$this->needsSchema();
		$messages = [];
		$dropped = false;

		foreach (TableSchema::GONE_INDICES as $tableName => $indexNames) {
			foreach ($indexNames as $indexName) {
				$message = $this->removeNamedIndexFromTable($tableName, $indexName);
				if ($message !== null) {
					$dropped = true;
					$messages[] = $message;
				}
			}
		}

		if (!$dropped) {
			$messages[] = 'No obsolete indices found';
		}

		return $messages;
	}

	/**
	 * remove all unique indices
	 *
	 * @return string[] logged messages
	 */
	public function removeAllUniqueIndices(): array {
		$messages = [];

		foreach (array_keys(TableSchema::UNIQUE_INDICES) as $tableName) {
			$messages = array_merge($messages, $this->removeUniqueIndicesFromTable($tableName));
		}

		return $messages;
	}

	/**
	 * remove all foreign keys from $tableName
	 *
	 * @param string $tableName name of table to remove fk from
	 * @return string[] logged messages
	 */
	public function removeForeignKeysFromTable(string $tableName): array {
		$this->needsSchema();
		$tableName = $this->getTableName($tableName);
		$messages = [];

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
	 *
	 * @param string $tableName table name of table to remove unique incices from
	 * @return string[] logged messages
	 */
	public function removeUniqueIndicesFromTable(string $tableName): array {
		$this->needsSchema();
		$tableName = $this->getTableName($tableName);
		$messages = [];

		if ($this->schema->hasTable($tableName)) {

			$table = $this->schema->getTable($tableName);

			foreach ($table->getIndexes() as $index) {
				if (stripos($index->getName(), 'UNIQ_') === 0) {
					$table->dropIndex($index->getName());
					$messages[] = 'Removed ' . $index->getName() . ' from ' . $tableName;
				}
			}
		}
		return $messages;
	}

	/**
	 * remove all generic indices from $table
	 *
	 * @param string $tableName table name of table to remove incices from
	 * @return string[] logged messages
	 */
	public function removeGenericIndicesFromTable(string $tableName): array {
		$this->needsSchema();
		$tableName = $this->getTableName($tableName);
		$messages = [];

		if ($this->schema->hasTable($tableName)) {

			$table = $this->schema->getTable($tableName);

			foreach ($table->getIndexes() as $index) {
				if (strpos($index->getName(), 'IDX_') === 0) {
					try {
						$messages[] = 'Remove ' . $index->getName() . ' from ' . $tableName;
						$table->dropIndex($index->getName());
					} catch (Exception $e) {
						/**
						 * If this fails, it is not a generic index, skip it
						 *
						 * This can happen if the index is already removed
						 * For some strange reason, an index name is
						 * reported, although it does not exist anymore
						 */
						continue;
					}
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
	public function removeNamedIndexFromTable(string $tableName, string $indexName): ?string {
		$this->needsSchema();
		$tableName = $this->getTableName($tableName);
		$message = null;

		try {
			if ($this->schema->hasTable($tableName)) {
				$table = $this->schema->getTable($tableName);
				$table->dropIndex($indexName);
				$message = 'Removed ' . $indexName . ' from ' . $tableName;
			}
		} catch (IndexDoesNotExist $e) {
			// common index does not exist, skip it
			$message = 'Skipped - Index ' . $indexName . ' does not exist in ' . $tableName;
		}
		return $message;
	}

	/**
	 * Create one index for a table, replacing any conflicting index by name or columns.
	 *
	 * If $indexName is empty, only the columns are checked (Doctrine auto-generates the name).
	 *
	 * @param string $tableName name of table to add the index to
	 * @param string $indexName index name, or empty string to let Doctrine auto-generate
	 * @param string[] $columns columns to include in the index
	 * @param bool $unique create a unique index
	 * @return string message
	 */
	public function createIndex(string $tableName, string $indexName, array $columns, bool $unique = false): string {
		$this->needsSchema();
		$prefixedTable = $this->getTableName($tableName);

		// Skip creation, if table does not exist
		if (!$this->schema->hasTable($prefixedTable)) {
			return 'Table ' . $prefixedTable . ' does not exist';
		}

		$table = $this->schema->getTable($prefixedTable);
		$hasName = $indexName !== '';
		$type = $unique ? 'unique index' : 'index';
		$message = [];

		// Check by name: if a named index already exists, verify its columns
		if ($hasName && $table->hasIndex($indexName)) {
			if ($this->columnsMatch($table->getIndex($indexName)->getColumns(), $columns)) {
				// Named index with same columns already exists, skip creation and return success message
				return ucfirst($type) . ' ' . $indexName . ' with correct configuration already exists in ' . $tableName . '. Skip creation.';
			}
			// Drop if named index with same name but different columns exists
			$table->dropIndex($indexName);
			$message[] = 'Dropped ' . $type . ' ' . $indexName . ' from ' . $tableName . ' (definition mismatch)';
		}

		// Check by columns: look further for any existing index with same uniqueness and same columns
		foreach ($table->getIndexes() as $index) {
			if ($index->isUnique() === $unique && $this->columnsMatch($index->getColumns(), $columns)) {
				if (!$hasName) {
					// If index is unnamed and index with matching configuraton is found, skip creation and return success message
					return ucfirst($type) . ' for ' . json_encode($columns) . ' already exists as ' . $index->getName() . ' in ' . $tableName;
				}
				// An index with same columns and uniqueness but different name exists, drop it
				$message[] = 'Dropped ' . $type . ' ' . $index->getName() . ' from ' . $tableName . ' (renamed to ' . $indexName . ')';
				$table->dropIndex($index->getName());
				break;
			}
		}

		// now create the new index, either, because it did not exist at all, or because the existing one(s) were dropped due to mismatch
		if ($unique) {
			$table->addUniqueIndex($columns, $hasName ? $indexName : null);
		} else {
			$table->addIndex($columns, $hasName ? $indexName : null);
		}
		$message[] = 'Added ' . $type . ' ' . ($hasName ? $indexName : '(auto)') . ' for ' . json_encode($columns) . ' to ' . $tableName;

		return implode('; ', $message);
	}

	/**
	 * Create unique indices
	 * Unique indices are crucial for the correct operation of the polls app.
	 * This for they have to be updated on every update.
	 *
	 * Falls back to dropping all unique indices and recreating them on error.
	 *
	 * @return string[] logged messages
	 */
	public function createUniqueIndices(): array {
		$messages = [];
		$this->needsSchema();

		try {
			foreach (TableSchema::UNIQUE_INDICES as $tableName => $uniqueIndices) {
				$prefixedTable = $this->getTableName($tableName);

				if (!$this->schema->hasTable($prefixedTable)) {
					$messages[] = 'Table ' . $prefixedTable . ' does not exist, skip unique index creation';
					continue;
				}

				foreach ($uniqueIndices as $name => $definition) {
					$messages[] = $this->createIndex($tableName, $name, $definition['columns'], true);
				}
			}
		} catch (Exception $e) {
			// If any exception was thrown, run a fallbach by dropping all unique indices and recreating them.
			// The app relies on the unique indices for correct operation.
			$messages[] = 'Failed creating unique indices (' . $e->getMessage() . '), falling back to complete recreation';

			foreach (TableSchema::UNIQUE_INDICES as $tableName => $uniqueIndices) {
				$prefixedTable = $this->getTableName($tableName);

				if (!$this->schema->hasTable($prefixedTable)) {
					$messages[] = 'Table ' . $prefixedTable . ' does not exist, skip unique index recreation';
					continue;
				}

				$table = $this->schema->getTable($prefixedTable);

				foreach ($table->getIndexes() as $index) {
					if ($index->isUnique() && !$index->isPrimary()) {
						$table->dropIndex($index->getName());
						$messages[] = 'Dropped unique index ' . $index->getName() . ' from ' . $tableName;
					}
				}

				foreach ($uniqueIndices as $name => $definition) {
					$table->addUniqueIndex($definition['columns'], $name);
					$messages[] = 'Recreated unique index ' . $name . ' in ' . $tableName;
				}
			}
		}

		return $messages;
	}
}
