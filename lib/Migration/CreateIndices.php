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

class CreateIndices implements IRepairStep {
	/** @var Connection */
	private $connection;

	/** @var SchemaWrapper */
	private $schema;

	public function __construct(Connection $connection) {
		$this->connection = $connection;
		$this->schema = new SchemaWrapper($this->connection);
	}

	public function getName() {
		return 'Polls - Create indices and foreign key constraints';
	}

	public function run(IOutput $output): void {
		$this->createForeignKeyConstraints();
		$this->createIndices();
		$this->migrate();

		$output->info('Polls - Foreign key contraints created.');
		$output->info('Polls - Indices created.');
	}

	/**
	 * execute the migration
	 */
	public function migrate() {
		$this->connection->migrateToSchema($this->schema->getWrappedSchema());
	}

	/**
	 * add on delete fk contraints to all tables referencing the main polls table
	 */
	public function createForeignKeyConstraints(): array {
		$messages = [];

		foreach (TableSchema::FK_CHILD_TABLES as $childTable) {
			$this->createForeignKeyConstraint(TableSchema::FK_PARENT_TABLE, $childTable);
			$messages[] = 'Add ' . TableSchema::FK_PARENT_TABLE . '[\'poll_id\'] <- ' . $childTable . '[\'id\']';
		}

		return $messages;
	}

	/**
	 * Create all indices
	 */
	public function createIndices(): array {
		$messages = [];

		foreach (TableSchema::UNIQUE_INDICES as $tableName => $values) {
			$this->createIndex($tableName, $values['name'], $values['columns'], $values['unique']);
			$messages[] = 'Added unique index ' . $values['name'] . ' to ' . $tableName;
		}

		// $this->connection->migrateToSchema($this->schema->getWrappedSchema());

		return $messages;
	}

	/**
	 * add an on delete fk contraint
	 */
	private function createForeignKeyConstraint(string $parentTableName, string $childTableName): void {
		$parentTable = $this->schema->getTable($parentTableName);
		$childTable = $this->schema->getTable($childTableName);

		$childTable->addForeignKeyConstraint($parentTable, ['poll_id'], ['id'], ['onDelete' => 'CASCADE']);

		// $this->connection->migrateToSchema($this->schema->getWrappedSchema());
	}
	
	/**
	 * Create index
	 */
	private function createIndex(string $tableName, string $indexName, array $columns, bool $unique = false): void {
		if ($this->schema->hasTable($tableName)) {
			$table = $this->schema->getTable($tableName);
			if (!$table->hasIndex($indexName)) {
				if ($unique) {
					$table->addUniqueIndex($columns, $indexName);
				} else {
					$table->addIndex($columns, $indexName);
				}
			}
		}
	}
}
