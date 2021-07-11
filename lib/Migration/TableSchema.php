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

use OCP\IDBConnection;
use OCP\DB\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use Doctrine\DBAL\Types\Type;

/**
 * Database definition for installing and migrations
 *
 * These definitions contain the base database layout
 *
 * used for initial migration to version 3.x from all prior versoins
 */

abstract class TableSchema {
	public const FK_PARENT_TABLE = 'polls_polls';

	public const FK_CHILD_TABLES = [
		'polls_comments',
		'polls_log',
		'polls_notif',
		'polls_options',
		'polls_share',
		'polls_votes',
	];

	public const UNIQUE_TABLES = [
		'polls_log',
		'polls_notif',
		'polls_options',
		'polls_preferences',
		'polls_share',
		'polls_votes',
	];

	public const UNIQUE_INDICES = [
		'polls_options' => ['name' => 'UNIQ_options', 'unique' => true, 'columns' => ['poll_id', 'poll_option_text', 'timestamp']],
		'polls_log' => ['name' => 'UNIQ_unprocessed', 'unique' => true, 'columns' => ['processed', 'poll_id', 'user_id', 'message_id']],
		'polls_notif' => ['name' => 'UNIQ_subscription', 'unique' => true, 'columns' => ['poll_id', 'user_id']],
		'polls_share' => ['name' => 'UNIQ_shares', 'unique' => true, 'columns' => ['poll_id', 'user_id']],
		'polls_votes' => ['name' => 'UNIQ_votes', 'unique' => true, 'columns' => ['poll_id', 'user_id', 'vote_option_text']],
		'polls_preferences' => ['name' => 'UNIQ_preferences', 'unique' => true, 'columns' => ['user_id']],
		'polls_watch' => ['name' => 'UNIQ_watch', 'unique' => true, 'columns' => ['poll_id', 'table']],
	];

	/**
	 * obsolete migration entries, which can be deleted
	 */
	public const GONE_MIGRATIONS = [
		'0001Date20000101120000',
		'0001Date20000101120001',
		'0009Date20181125051900',
		'0009Date20181125061900',
		'0009Date20181125062101',
		'0010Date20191227063812',
		'0010Date20200119101800',
		'0101Date20200122194300',
		'0103Date20200130171244',
		'0104Date20200205104800',
		'0104Date20200314074611',
		'0105Date20200508211943',
		'0105Date20200523142076',
		'0105Date20200704084037',
		'0105Date20200903172733',
		'0106Date20201031080745',
		'0106Date20201031080946',
		'0107Date20201210204702',
		'0107Date20201210213303',
		'0107Date20201217071304',
		'0107Date20210101161105',
		'0107Date20210104135506',
		'0107Date20210121220707',
		'0108Date20210117010101',
		'0108Date20210127135802',
		'0108Date20210207134703',
		'0108Date20210307130001',
		'0108Date20210307130003',
		'0108Date20210307130009',
		'0109Date20210323120002',
		'030000Date20210611120000',
	];

	/**
	 * define obsolete tables, which do get migrated as an array of table names
	 */
	public const GONE_TABLES = [
		'polls_events',
		'polls_dts',
		'polls_txts',
		'polls_particip',
		'polls_particip_text',
		'polls_test',
	];

	/** define obsolete columns, which do get migrated
	 * Format:
	 * public const GONE_COLUMNS = [
	 *   'tableName1' => [
	 *     'columnName1',
	 *     'columnName2',
	 *     ...,
	 *   ],
	 *   'tableName2' => [
	 *     'columnName1',
	 *     'columnName2',
	 *     ...,
	 *   ],
	 *   ...,
	 * ];
	 */
	public const GONE_COLUMNS = [
		'polls_polls' => [
			'full_anonymous',
			'options',
			'settings',
		],
		'polls_comments' => [
			'dt',
		],
	];

	/**
	 * define primary keys (only set on table creation)
	 * Format:
	 * public const PRIMARYKEY = [
	 *   'polls_polls' => [
	 *     'id' => [
	 *       'type' => Types::INTEGER,
	 *       'options' => ['autoincrement' => true, 'notnull' => true]
	 *     ],
	 *     'type' => [
	 *       'type' => Types::STRING,
	 *       'options' => ['notnull' => true, 'default' => 'datePoll', 'length' => 64]],
	 *     ],
	 *     ...,
	 *   ],
	 *   'polls_options' => [
	 *     'id' => [
	 *       'type' => Types::INTEGER,
	 *       'options' => ['autoincrement' => true, 'notnull' => true]
	 *     ],
	 *     'poll_id' => [
	 *       'type' => Types::INTEGER,
	 *       'options' => ['notnull' => true, 'default' => 0]
	 *     ],
	 *     ...,
	 *   ],
	 *   ...,
	 * ];
	 */

	public const TABLES = [
		'polls_polls' => [
			'id' => ['type' => Types::INTEGER, 'options' => ['autoincrement' => true, 'notnull' => true]],
			'type' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'datePoll', 'length' => 64]],
			'title' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 128]],
			'description' => ['type' => Types::TEXT, 'options' => ['notnull' => false, 'default' => '', 'length' => 65535]],
			'owner' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'created' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'expire' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'deleted' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'access' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'hidden', 'length' => 1024]],
			'anonymous' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'allow_maybe' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 1]],
			'vote_limit' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'option_limit' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'show_results' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'always', 'length' => 64]],
			'admin_access' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'important' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'allow_comment' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 1]],
			'hide_booked_up' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 1]],
			'allow_proposals' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'disallow', 'length' => 64]],
			'use_no' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 1]],
			'proposals_expire' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
		],
		'polls_options' => [
			'id' => ['type' => Types::INTEGER, 'options' => ['autoincrement' => true, 'notnull' => true]],
			'poll_id' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'poll_option_text' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'timestamp' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'duration' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'order' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'confirmed' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'owner' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'released' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
		],
		'polls_votes' => [
			'id' => ['type' => Types::INTEGER, 'options' => ['autoincrement' => true, 'notnull' => true]],
			'poll_id' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'vote_option_id' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'vote_option_text' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'vote_answer' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 64]],
		],
		'polls_comments' => [
			'id' => ['type' => Types::INTEGER, 'options' => ['autoincrement' => true, 'notnull' => true]],
			'poll_id' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'comment' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 1024]],
			'timestamp' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],

		],
		'polls_share' => [
			'id' => ['type' => Types::INTEGER, 'options' => ['autoincrement' => true, 'notnull' => true]],
			'token' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 64]],
			'type' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 64]],
			'poll_id' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'display_name' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'email_address' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'invitation_sent' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
		],
		'polls_notif' => [
			'id' => ['type' => Types::INTEGER, 'options' => ['autoincrement' => true, 'notnull' => true]],
			'poll_id' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
		],
		'polls_log' => [
			'id' => ['type' => Types::INTEGER, 'options' => ['autoincrement' => true, 'notnull' => true]],
			'poll_id' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'display_name' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'message_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 64]],
			'created' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'processed' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
		],
		'polls_watch' => [
			'id' => ['type' => Types::INTEGER, 'options' => ['autoincrement' => true, 'notnull' => true]],
			'poll_id' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'table' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 64]],
			'updated' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
		],
		'polls_preferences' => [
			'id' => ['type' => Types::INTEGER, 'options' => ['autoincrement' => true, 'notnull' => true]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'timestamp' => ['type' => Types::INTEGER, 'options' => ['notnull' => true, 'default' => 0]],
			'preferences' => ['type' => Types::TEXT, 'options' => ['notnull' => false, 'default' => '', 'length' => 65535]],
		],
	];

	/**
	 * Iterate over tables and make sure, the are created or updated
	 * according to the schema
	 */
	public static function createOrUpdateSchema(ISchemaWrapper &$schema, IOutput &$output): void {
		foreach (self::TABLES as $tableName => $columns) {
			$tableCreated = false;

			if ($schema->hasTable($tableName)) {
				$output->info('Validating table ' . $tableName);
				$table = $schema->getTable($tableName);
			} else {
				$output->info('Creating table ' . $tableName);
				$table = $schema->createTable($tableName);
				$tableCreated = true;
			}

			foreach ($columns as $columnName => $columnDefinition) {
				if ($table->hasColumn($columnName)) {
					$column = $table->getColumn($columnName);
					$column->setOptions($columnDefinition['options']);
					if ($column->getType()->getName() !== $columnDefinition['type']) {
						$output->info('Migrating type of ' . $tableName . ', ' . $columnName . ' to ' . $columnDefinition['type']);
						$column->setType(Type::getType($columnDefinition['type']));
					}

					// force change to current options definition
					$table->changeColumn($columnName, $columnDefinition['options']);
				} else {
					$table->addColumn($columnName, $columnDefinition['type'], $columnDefinition['options']);
				}
			}

			if ($tableCreated) {
				$table->setPrimaryKey(['id']);
			}
		}
	}

	/**
	 * Remove obsolete tables if they still exist
	 */
	public static function removeObsoleteTables(ISchemaWrapper &$schema, IOutput &$output): void {
		foreach (self::GONE_TABLES as $tableName) {
			if ($schema->hasTable($tableName)) {
				$schema->dropTable($tableName);
				$output->info('Dropped orphaned table ' . $tableName);
			}
		}
	}

	/**
	 * Remove obsolete columns, if they exist
	 */
	public static function removeObsoleteColumns(ISchemaWrapper &$schema, IOutput &$output): void {
		foreach (self::GONE_COLUMNS as $tableName => $columns) {
			if ($schema->hasTable($tableName)) {
				$table = $schema->getTable($tableName);

				foreach ($columns as $columnName) {
					if ($table->hasColumn($columnName)) {
						$table->dropColumn($columnName);
						$output->info('Dropped obsolete column ' . $columnName . ' from ' . $tableName);
					}
				}
			}
		}
	}

	/**
	 * Tidy migrations table and remove obsolete migration entries.
	 */
	public static function removeObsoleteMigrations(IDBConnection &$connection, IOutput &$output): void {
		$query = $connection->getQueryBuilder();
		$output->info('tidy migration entries');
		foreach (self::GONE_MIGRATIONS as $version) {
			$query->delete('migrations')
				->where('app = :appName')
				->andWhere('version = :version')
				->setParameter('appName', 'polls')
				->setParameter('version', $version)
				->execute();
		}
	}
}
