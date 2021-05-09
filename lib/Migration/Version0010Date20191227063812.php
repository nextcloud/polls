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
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\Security\ISecureRandom;

class Version0010Date20191227063812 extends SimpleMigrationStep {

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

		if ($schema->hasTable('polls_comments')) {
			$table = $schema->getTable('polls_comments');
			if (!$table->hasColumn('timestamp')) {
				$table->addColumn('timestamp', 'integer', [
					'length' => 11,
					'notnull' => true,
					'default' => 0
				]);
			}
		}

		if (!$schema->hasTable('polls_polls')) {
			$table = $schema->createTable('polls_polls');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'length' => 11,
				'notnull' => true
			]);
			$table->addColumn('type', 'string', [
				'length' => 64,
				'notnull' => true,
				'default' => 'datePoll'
			]);
			$table->addColumn('title', 'string', [
				'length' => 128,
				'notnull' => true
			]);
			$table->addColumn('description', 'string', [
				'length' => 1024,
				'notnull' => true
			]);
			$table->addColumn('owner', 'string', [
				'length' => 64,
				'notnull' => true
			]);
			$table->addColumn('created', 'integer', [
				'length' => 11,
				'notnull' => true,
				'default' => 0
			]);
			$table->addColumn('expire', 'integer', [
				'length' => 11,
				'notnull' => true,
				'default' => 0
			]);
			$table->addColumn('deleted', 'integer', [
				'length' => 11,
				'notnull' => true,
				'default' => 0
			]);
			$table->addColumn('access', 'string', [
				'notnull' => true,
				'length' => 1024,
				'default' => 'hidden'
			]);
			$table->addColumn('anonymous', 'integer', [
				'length' => 8,
				'notnull' => true,
				'default' => 0
			]);
			$table->addColumn('full_anonymous', 'integer', [
				'notnull' => true,
				'default' => 0,
			]);
			$table->addColumn('allow_maybe', 'integer', [
				'notnull' => true,
				'default' => 1
			]);
			$table->addColumn('options', 'text', [
				'notnull' => true,
				'default' => ''
			]);
			$table->addColumn('settings', 'text', [
				'notnull' => true,
				'default' => ''
			]);
			$table->addColumn('vote_limit', 'integer', [
				'length' => 11,
				'notnull' => true,
				'default' => 0
			]);
			$table->addColumn('show_results', 'string', [
				'length' => 64,
				'notnull' => true,
				'default' => 'always'
			]);
			$table->addColumn('admin_access', 'integer', [
				'length' => 8,
				'notnull' => true,
				'default' => 0
			]);

			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_share')) {
			$table = $schema->createTable('polls_share');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('token', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('type', 'string', [
				'notnull' => true,
				'length' => 64
			]);
			$table->addColumn('poll_id', 'integer', [
				'notnull' => true
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => false,
				'length' => 64
			]);
			$table->addColumn('user_email', 'string', [
				'notnull' => false,
				'length' => 254
			]);
			$table->addColumn('user', 'text', [
				'notnull' => true,
				'default' => ''
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('polls_log')) {
			$table = $schema->createTable('polls_log');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true
			]);
			$table->addColumn('created', 'integer', [
				'notnull' => true,
				'length' => 11,
				'default' => 0
			]);
			$table->addColumn('processed', 'integer', [
				'notnull' => true,
				'length' => 11,
				'default' => 0
			]);
			$table->addColumn('poll_id', 'integer', [
				'notnull' => true
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => false,
				'length' => 1024
			]);
			$table->addColumn('display_name', 'string', [
				'notnull' => false,
				'length' => 64
			]);
			$table->addColumn('message_id', 'string', [
				'notnull' => false,
				'length' => 64
			]);
			$table->addColumn('message', 'string', [
				'notnull' => false,
				'length' => 1024
			]);
			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}

	/**
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('polls_polls') &&
			$schema->hasTable('polls_events')) {
			$this->migrateEvents();
		}

		if ($schema->hasTable('polls_share') &&
			$schema->hasTable('polls_events')) {
			$this->copyTokens();
		}
	}


	private function resolveAccess(string $access): string {
		if ($access === 'public') {
			return 'public';
		}
		return 'hidden';
	}

	/**
	 * @return string
	 */
	private function resolveOptions(bool $maybe) {
		if ($maybe) {
			return json_encode(['yes', 'no', 'maybe']);
		}
		return json_encode(['yes', 'no']);
	}

	private function resolveType(string $type): string {
		if ($type) {
			return 'textPoll';
		}
		return 'datePoll';
	}

	protected function migrateEvents(): void {
		$insert = $this->connection->getQueryBuilder();
		$insert
			->insert('polls_polls')
			->values([
				'id' => $insert->createParameter('id'),
				'type' => $insert->createParameter('type'),
				'title' => $insert->createParameter('title'),
				'description' => $insert->createParameter('description'),
				'owner' => $insert->createParameter('owner'),
				'created' => $insert->createParameter('created'),
				'expire' => $insert->createParameter('expire'),
				'deleted' => $insert->createParameter('deleted'),
				'access' => $insert->createParameter('access'),
				'anonymous' => $insert->createParameter('anonymous'),
				'full_anonymous' => $insert->createParameter('full_anonymous'),
				'allow_maybe' => $insert->createParameter('allow_maybe'),
				'options' => $insert->createParameter('options'),
				'settings' => $insert->createParameter('settings'),
				'vote_limit' => $insert->createParameter('vote_limit'),
				'show_results' => $insert->createParameter('show_results'),
				'admin_access' => $insert->createParameter('admin_access')
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')->from('polls_events');
		$result = $query->execute();

		while ($row = $result->fetch()) {
			$insert
			->setParameter('id', $row['id'])
			->setParameter('type', $this->resolveType($row['type']))
			->setParameter('title', $row['title'])
			->setParameter('description', $row['description'])
			->setParameter('owner', $row['owner'])
			->setParameter('created', intval(strtotime($row['created'])))
			->setParameter('expire', intval(strtotime($row['expire'])))
			->setParameter('deleted', 0)
			->setParameter('access', $this->resolveAccess($row['access']))
			->setParameter('anonymous', intval($row['full_anonymous']) * 2 + intval($row['is_anonymous']))
			->setParameter('full_anonymous', $row['full_anonymous'])
			->setParameter('allow_maybe', $row['allow_maybe'])
			->setParameter('options', $this->resolveOptions($row['allow_maybe']))
			->setParameter('settings', '')
			->setParameter('vote_limit', 0)
			->setParameter('show_results', 'always')
			->setParameter('admin_access', 0);
			$insert->execute();
		}

		$result->closeCursor();
	}

	/**
	 * Copy public tokens
	 */
	protected function copyTokens(): void {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('polls_share')
			->values([
				'token' => $insert->createParameter('token'),
				'type' => $insert->createParameter('type'),
				'poll_id' => $insert->createParameter('poll_id'),
				'user_id' => $insert->createParameter('user_id'),
				'user_email' => $insert->createParameter('user_email'),
				'user' => $insert->createParameter('user')
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('polls_events');
		$result = $query->execute();

		while ($row = $result->fetch()) {
			if ($row['access'] === 'public') {
				// copy the hash to a public share
				$insert
				->setParameter('token', $row['hash'])
				->setParameter('type', 'public')
				->setParameter('poll_id', $row['id'])
				->setParameter('user_id', null)
				->setParameter('user_email', null)
				->setParameter('user', '');
				$insert->execute();
			} elseif ($row['access'] === 'hidden') {
				// copy the hash to a public share
				// poll stays hidden for registered users
				$insert
				->setParameter('token', $row['hash'])
				->setParameter('type', 'public')
				->setParameter('poll_id', $row['id'])
				->setParameter('user_id', null)
				->setParameter('user_email', null)
				->setParameter('user', '');
				$insert->execute();
			} elseif ($row['access'] === 'registered') {
				// copy the hash to a public share
				// to keep the hash
				$insert
				->setParameter('token', $row['hash'])
				->setParameter('type', 'public')
				->setParameter('poll_id', $row['id'])
				->setParameter('user_id', null)
				->setParameter('user_email', null)
				->setParameter('user', '');
			} else {
				// create a personal share for invitated users

				// explode the access entry to single access strings
				$users = explode(';', $row['access']);
				foreach ($users as $value) {
					// separate 'user' and 'group' from user names and create
					// a share for every entry
					$parts = explode('_', $value);
					$insert
					->setParameter('token', \OC::$server->getSecureRandom()->generate(
						16,
						ISecureRandom::CHAR_DIGITS .
						ISecureRandom::CHAR_LOWER .
						ISecureRandom::CHAR_UPPER
					))
					->setParameter('type', $parts[0])
					->setParameter('poll_id', $row['id'])
					->setParameter('user_id', $parts[1])
					->setParameter('user_email', null)
					->setParameter('user', '');
					$insert->execute();
				}
			}
		}
		$result->closeCursor();
	}
}
