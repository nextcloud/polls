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

	// private const INDICES = [
	//    'polls_options' => ['name' => 'UNIQ_options', 'unique' => true, 'columns' => ['poll_id', 'poll_option_text', 'timestamp']],
	//    'polls_log' => ['name' => 'UNIQ_unprocessed', 'unique' => true, 'columns' => ['processed', 'poll_id', 'user_id', 'message_id']],
	//    'polls_notif' => ['name' => 'UNIQ_subscription', 'unique' => true, 'columns' => ['poll_id', 'user_id']],
	//    'polls_share' => ['name' => 'UNIQ_shares', 'unique' => true, 'columns' => ['poll_id', 'user_id']],
	//    'polls_votes' => ['name' => 'UNIQ_votes', 'unique' => true, 'columns' => ['poll_id', 'user_id', 'vote_option_text']],
	//    'polls_preferences' => ['name' => 'UNIQ_preferences', 'unique' => true, 'columns' => ['user_id']],
	//    'polls_watch' => ['name' => 'UNIQ_watch', 'unique' => true, 'columns' => ['poll_id', 'table']],
	// ];
	//
	// private const PARENT_TABLE = 'polls_polls';
	//
	// private const CHILD_TABLES = [
	//    'polls_comments',
	//    'polls_log',
	//    'polls_notif',
	//    'polls_options',
	//    'polls_share',
	//    'polls_votes',
	// ];

	/** @var Connection */
	private $connection;

	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	public function getName() {
		return 'Polls - Create indices and foreign key constraints';
	}

	public function run(IOutput $output): void {

		foreach (TableSchema::UNIQUE_INDICES as $tableName => $values) {
			$this->createIndex($tableName, $values['name'], $values['columns'], $values['unique']);
		}
		$output->info('Polls - Indices created.');

		$this->createForeignKeyConstraints();
		$output->info('Polls - Foreign key contraints created.');
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function createForeignKeyConstraints(): void {
		$schema = new SchemaWrapper($this->connection);
		$eventTable = $schema->getTable(TableSchema::FK_PARENT_TABLE);
		foreach (TableSchema::FK_CHILD_TABLES as $tbl) {
			$table = $schema->getTable($tbl);
			$table->addForeignKeyConstraint($eventTable, ['poll_id'], ['id'], ['onDelete' => 'CASCADE']);
		}
		$this->connection->migrateToSchema($schema->getWrappedSchema());
	}

	/**
	 * Create index for $table
	 */
	private function createIndex(string $tableName, string $indexName, array $columns, bool $unique = false): void {
		$schema = new SchemaWrapper($this->connection);
		if ($schema->hasTable($tableName)) {
			$table = $schema->getTable($tableName);
			if (!$table->hasIndex($indexName)) {
				if ($unique) {
					$table->addUniqueIndex($columns, $indexName);
				} else {
					$table->addIndex($columns, $indexName);
				}
				$this->connection->migrateToSchema($schema->getWrappedSchema());
			}
		}
	}
}
