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

use OCP\DB\Types;
use OCP\DB\ISchemaWrapper;
use OCA\Polls\Db\Comment;
use OCA\Polls\Db\Log;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\Preferences;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\Watch;
use Doctrine\DBAL\Types\Type;

/**
 * Database definition for installing and migrations
 * These definitions contain the base database layout
 * used for initial migration to version 3.x from all prior versoins
 */

abstract class TableSchema {
	public const FK_PARENT_TABLE = Poll::TABLE;

	public const FK_CHILD_TABLES = [
		Comment::TABLE,
		Log::TABLE,
		Subscription::TABLE,
		Option::TABLE,
		Share::TABLE,
		Vote::TABLE,
	];

	public const UNIQUE_INDICES = [
		Option::TABLE => ['name' => 'UNIQ_options', 'unique' => true, 'columns' => ['poll_id', 'poll_option_text', 'timestamp']],
		Log::TABLE => ['name' => 'UNIQ_unprocessed', 'unique' => true, 'columns' => ['processed', 'poll_id', 'user_id', 'message_id']],
		Subscription::TABLE => ['name' => 'UNIQ_subscription', 'unique' => true, 'columns' => ['poll_id', 'user_id']],
		Share::TABLE => ['name' => 'UNIQ_shares', 'unique' => true, 'columns' => ['poll_id', 'user_id']],
		Vote::TABLE => ['name' => 'UNIQ_votes', 'unique' => true, 'columns' => ['poll_id', 'user_id', 'vote_option_text']],
		Preferences::TABLE => ['name' => 'UNIQ_preferences', 'unique' => true, 'columns' => ['user_id']],
		Watch::TABLE => ['name' => 'UNIQ_watch', 'unique' => true, 'columns' => ['poll_id', 'table', 'session_id']],
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
		'030000Date20210704120000',
		'030200Date20210912120000',
		'030400Date20211125120000',
		'040100Date20221030070000',
		'041000Date20221221070000',
		'040101Date20230119080000',
	];

	/**
	 * define obsolete tables to drop
	 */
	public const GONE_TABLES = [
		'polls_events', // dropped in 1.0
		'polls_dts', // dropped in 0.9
		'polls_txts', // dropped in 0.9
		'polls_particip', // dropped in 0.9
		'polls_particip_text', // dropped in 0.9
		'polls_test', // invalid table, accidentially introduced in an old beta version
	];

	/**
	 * define obsolete columns to drop
	 */
	public const GONE_COLUMNS = [
		Poll::TABLE => [
			'full_anonymous', // dropped in 3.0, orphaned
			'options', // dropped in 3.0, orphaned
			'settings', // dropped in 3.0, orphaned
		],
		Comment::TABLE => [
			'dt', // dropped in 3.0, orphaned
		],
		Share::TABLE => [
			'user', // dropped in 1.01
			'user_email', // dropped in 1.06 and migrated to email_address
		],
		Log::TABLE => [
			'message', // dropped in 1.07, orphaned
		],
	];

	/**
	 * define table structure
	 */
	public const TABLES = [
		Poll::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'type' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'datePoll', 'length' => 64]],
			'title' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => '', 'length' => 128]],
			'description' => ['type' => Types::TEXT, 'options' => ['notnull' => true, 'default' => '', 'length' => 65535]],
			'owner' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'created' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'expire' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'deleted' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'access' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'private', 'length' => 1024]],
			'anonymous' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'allow_maybe' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 1, 'length' => 20]],
			'vote_limit' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'option_limit' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'show_results' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'always', 'length' => 64]],
			'admin_access' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'important' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'allow_comment' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 1, 'length' => 20]],
			'hide_booked_up' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 1, 'length' => 20]],
			'allow_proposals' => ['type' => Types::STRING, 'options' => ['notnull' => true, 'default' => 'disallow', 'length' => 64]],
			'use_no' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 1, 'length' => 20]],
			'proposals_expire' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'misc_settings' => ['type' => Types::TEXT, 'options' => ['notnull' => false, 'default' => '', 'length' => 65535]],
		],
		Option::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'poll_option_text' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 1024]],
			'timestamp' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'duration' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'order' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'confirmed' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'owner' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'released' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
		],
		Vote::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'vote_option_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'vote_option_text' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 1024]],
			'vote_answer' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 64]],
		],
		Comment::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'comment' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 1024]],
			'timestamp' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],

		],
		Share::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'token' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 64]],
			'type' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 64]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'display_name' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'email_address' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'invitation_sent' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'reminder_sent' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'misc_settings' => ['type' => Types::TEXT, 'options' => ['notnull' => false, 'default' => '', 'length' => 65535]],
		],
		Subscription::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
		],
		Log::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'display_name' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'message_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 64]],
			'created' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'processed' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
		],
		Watch::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'poll_id' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'table' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 64]],
			'updated' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'session_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => null]],
		],
		Preferences::TABLE => [
			'id' => ['type' => Types::BIGINT, 'options' => ['autoincrement' => true, 'notnull' => true, 'length' => 20]],
			'user_id' => ['type' => Types::STRING, 'options' => ['notnull' => false, 'default' => '', 'length' => 256]],
			'timestamp' => ['type' => Types::BIGINT, 'options' => ['notnull' => true, 'default' => 0, 'length' => 20]],
			'preferences' => ['type' => Types::TEXT, 'options' => ['notnull' => false, 'default' => '', 'length' => 65535]],
		],
	];

	/**
	 * Iterate over tables and make sure, they are created or updated
	 * according to the currently valid schema
	 */
	public static function createOrUpdateSchema(ISchemaWrapper &$schema): array {
		$messages = [];
		foreach (self::TABLES as $tableName => $columns) {
			$tableCreated = false;

			if ($schema->hasTable($tableName)) {
				$messages[] = 'Validating table ' . $tableName;
				$table = $schema->getTable($tableName);
			} else {
				$messages[] = 'Creating table ' . $tableName;
				$table = $schema->createTable($tableName);
				$tableCreated = true;
			}

			foreach ($columns as $columnName => $columnDefinition) {
				if ($table->hasColumn($columnName)) {
					$column = $table->getColumn($columnName);
					$column->setOptions($columnDefinition['options']);
					if ($column->getType()->getName() !== $columnDefinition['type']) {
						$messages[] = 'Migrating type of ' . $tableName . ', ' . $columnName . ' to ' . $columnDefinition['type'];
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
		return $messages;
	}

	// /**
	//  * Remove obsolete tables if they still exist
	//  */
	// public static function removeObsoleteTables(ISchemaWrapper &$schema): array {
	// 	$messages = [];
	// 	foreach (self::GONE_TABLES as $tableName) {
	// 		if ($schema->hasTable($tableName)) {
	// 			$schema->dropTable($tableName);
	// 			$messages[] = 'Dropped table ' . $tableName;
	// 		}
	// 	}
	// 	return $messages;
	// }

	// /**
	//  * Remove obsolete columns, if they exist
	//  */
	// public static function removeObsoleteColumns(ISchemaWrapper &$schema): array {
	// 	$messages = [];
	// 	foreach (self::GONE_COLUMNS as $tableName => $columns) {
	// 		if ($schema->hasTable($tableName)) {
	// 			$table = $schema->getTable($tableName);

	// 			foreach ($columns as $columnName) {
	// 				if ($table->hasColumn($columnName)) {
	// 					$table->dropColumn($columnName);
	// 					$messages[] = 'Dropped obsolete column ' . $columnName . ' from ' . $tableName;
	// 				}
	// 			}
	// 		}
	// 	}
	// 	return $messages;
	// }

	// /**
	//  * Tidy migrations table and remove obsolete migration entries.
	//  */
	// public static function removeObsoleteMigrations(IDBConnection &$connection): array {
	// 	$messages = [];
	// 	$query = $connection->getQueryBuilder();
	// 	$messages[] = 'tidy migration entries';
	// 	foreach (self::GONE_MIGRATIONS as $version) {
	// 		$messages[] = '- remove ' . $version;
	// 		$query->delete('migrations')
	// 			->where('app = :appName')
	// 			->andWhere('version = :version')
	// 			->setParameter('appName', 'polls')
	// 			->setParameter('version', $version)
	// 			->executeStatement();
	// 	}
	// 	return $messages;
	// }
}
