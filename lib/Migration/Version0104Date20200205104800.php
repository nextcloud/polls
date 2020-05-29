<?php
/**
 * @copyright Copyright (c) 2017 René Gieling <github@dartcafe.de>
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
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Installation class for the polls app.
 * Initial db creation
 */
class Version0104Date20200205104800 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;

	/** @var array */
	protected $childTables = [
		'polls_comments',
		'polls_log',
		'polls_notif',
		'polls_options',
		'polls_share',
		'polls_votes',
	];

	/**
	 * @param IDBConnection $connection
	 * @param IConfig $config
	 */
	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}


	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null
	 * @since 13.0.0
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		// delete all orphaned entries by selecting all rows
		// those poll_ids are not present in the polls table
		//
		// we have to use a raw query, because NOT EXISTS is not
		// part of doctrine's expression builder
		//
		// get table prefix, as we are running a raw query
		$prefix = $this->config->getSystemValue('dbtableprefix', 'oc_');
		// check for orphaned entries in all tables referencing
		// the main polls table
		foreach ($this->childTables as $tbl) {
			$child = "$prefix$tbl";
			$query = "DELETE
                FROM $child
                WHERE NOT EXISTS (
                    SELECT NULL
                    FROM {$prefix}polls_polls polls
                    WHERE polls.id = {$child}.poll_id
                )";
			$stmt = $this->connection->prepare($query);
			$stmt->execute();
		}
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		// add an on delete fk contraint to all tables referencing the main polls table
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$eventTable = $schema->getTable('polls_polls');
		foreach ($this->childTables as $tbl) {
			$table = $schema->getTable($tbl);

			$table->addForeignKeyConstraint($eventTable, ['poll_id'], ['id'], ['onDelete' => 'CASCADE']);
		}

		return $schema;
	}
}
