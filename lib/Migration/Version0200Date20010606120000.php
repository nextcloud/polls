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
use OCP\DB\Types;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Installation class for the polls app.
 * Initial db creation
 */
class Version0200Date20010606120000 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;

	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}

	/**
	 * $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable('polls_polls')) {
			$table = $schema->createTable('polls_polls');
			$table->addColumn('id', TYPES::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('type', TYPES::STRING, [
				'notnull' => true,
				'default' => 'datePoll',
				'length' => 64,
			]);
			$table->addColumn('title', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 128,
			]);
			$table->addColumn('description', TYPES::TEXT, [
				'notnull' => true,
				'default' => '',
			]);
			$table->addColumn('owner', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('created', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('expire', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('deleted', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('access', TYPES::STRING, [
				'notnull' => true,
				'default' => 'hidden',
				'length' => 1024,
			]);
			$table->addColumn('anonymous', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('allow_maybe', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 1,
				'length' => 11,
			]);
			$table->addColumn('vote_limit', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('option_limit', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('show_results', TYPES::STRING, [
				'notnull' => true,
				'default' => 'always',
				'length' => 64,
			]);
			$table->addColumn('admin_access', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 8,
			]);
			$table->addColumn('important', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('allow_comment', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 1,
				'length' => 11,
			]);
			$table->addColumn('hide_booked_up', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 1,
				'length' => 11,
			]);
			$table->addColumn('allow_proposals', TYPES::STRING, [
				'notnull' => true,
				'default' => 'disallow',
				'length' => 64,
			]);
			$table->addColumn('use_no', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 1,
				'length' => 11,
			]);
			$table->addColumn('proposals_expire', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);

			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_options')) {
			$table = $schema->createTable('polls_options');
			$table->addColumn('id', TYPES::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('poll_id', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('poll_option_text', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 256,
			]);
			$table->addColumn('timestamp', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('duration', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('order', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('confirmed', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('owner', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('released', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_votes')) {
			$table = $schema->createTable('polls_votes');
			$table->addColumn('id', TYPES::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('poll_id', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('user_id', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('vote_option_id', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 64,
			]);
			$table->addColumn('vote_option_text', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 256,
			]);
			$table->addColumn('vote_answer', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_comments')) {
			$table = $schema->createTable('polls_comments');
			$table->addColumn('id', TYPES::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('user_id', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('comment', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 1024,
			]);
			$table->addColumn('timestamp', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_notif')) {
			$table = $schema->createTable('polls_notif');
			$table->addColumn('id', TYPES::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('poll_id', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('user_id', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_share')) {
			$table = $schema->createTable('polls_share');
			$table->addColumn('id', TYPES::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('token', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('type', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('poll_id', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('user_id', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('invitation_sent', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('display_name', TYPES::STRING, [
				'notnull' => false,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('email_address', TYPES::STRING, [
				'notnull' => false,
				'default' => '',
				'length' => 254,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_log')) {
			$table = $schema->createTable('polls_log');
			$table->addColumn('id', TYPES::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('created', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('processed', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('poll_id', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('user_id', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('display_name', TYPES::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('message_id', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);

			$table->setPrimaryKey(['id']);
		}

		// since polls 1.5
		// Version0105Date20200523142076
		if (!$schema->hasTable('polls_preferences')) {
			$table = $schema->createTable('polls_preferences');

			$table->addColumn('id', TYPES::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('user_id', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('timestamp', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('preferences', TYPES::TEXT, [
				'notnull' => false,
				'default' => '',
			]);
			$table->setPrimaryKey(['id']);
		}

		// since polls 1.8
		// Version0108Date20210307130003
		if (!$schema->hasTable('polls_watch')) {
			$table = $schema->createTable('polls_watch');
			$table->addColumn('id', TYPES::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
			]);
			$table->addColumn('table', TYPES::STRING, [
				'notnull' => true,
				'default' => '',
				'length' => 64,
			]);
			$table->addColumn('poll_id', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->addColumn('updated', TYPES::INTEGER, [
				'notnull' => true,
				'default' => 0,
				'length' => 11,
			]);
			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}

}
