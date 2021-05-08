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

	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	public function getName() {
		return 'Create polls table indices';
	}

	/**
	 * @return void
	 */
	public function run(IOutput $output) {
		$this->createIndex('polls_options', 'UNIQ_options', ['poll_id', 'poll_option_text', 'timestamp'], true);
		$this->createIndex('polls_log', 'UNIQ_unprocessed', ['processed', 'poll_id', 'user_id', 'message_id'], true);
		$this->createIndex('polls_notif', 'UNIQ_subscription', ['poll_id', 'user_id'], true);
		$this->createIndex('polls_share', 'UNIQ_shares', ['poll_id', 'user_id'], true);
		$this->createIndex('polls_votes', 'UNIQ_votes', ['poll_id', 'user_id', 'vote_option_text'], true);
		$this->createIndex('polls_preferences', 'UNIQ_preferences', ['user_id'], true);
		$this->createIndex('polls_watch', 'UNIQ_watch', ['poll_id', 'table'], true);
	}

	/**
	 * Create index for $table
	 *
	 * @return void
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
