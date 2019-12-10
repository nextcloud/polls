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

// use Doctrine\DBAL\Exception\TableNotFoundException;
// use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\Security\ISecureRandom;

/**
 * Installation class for the polls app.
 * Initial db creation
 */
class Version0010Date20190801063812 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;

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
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('polls_events')) {
			$table = $schema->getTable('polls_events');
			if (!$table->hasColumn('deleted')) {
				$table->addColumn('deleted', Type::BOOLEAN, [
					'notnull' => false,
					'default' => 0
				]);
			}
			if (!$table->hasColumn('delete_date')) {
				$table->addColumn('delete_date', Type::DATETIME, [
					'notnull' => false
				]);
			}
			if (!$table->hasColumn('vote_limit')) {
				$table->addColumn('vote_limit', Type::INTEGER, [
					'notnull' => false,
					'default' => 0
				]);
			}
			if (!$table->hasColumn('show_results')) {
				$table->addColumn('show_results', Type::STRING, [
					'notnull' => true,
					'lenght' => 64
				]);
			}

		}
		if (!$schema->hasTable('polls_share')) {
			$table = $schema->createTable('polls_share');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('token', Type::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('type', Type::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => true
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('user_email', Type::STRING, [
				'notnull' => false,
				'length' => 254,
			]);
			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('polls_share')) {
			$this->copyTokens();
			// $this->copyInvitationTokens();
		}
	}

	/**
	 * Copy public tokens
	 */
	protected function copyTokens() {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('polls_share')
			->values([
				'token' => $insert->createParameter('token'),
				'type' => $insert->createParameter('type'),
				'poll_id' => $insert->createParameter('poll_id'),
				'user_id' => $insert->createParameter('user_id'),
				'user_email' => $insert->createParameter('user_email')
			]);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('polls_events');
		$result = $query->execute();

		while ($row = $result->fetch()) {
			if ($row['access'] == 'public') {
				// copy the token to a public share
				$insert
				->setParameter('token', $row['token'])
				->setParameter('type', 'public')
				->setParameter('poll_id', $row['id'])
				->setParameter('user_id', null)
				->setParameter('user_email', null);
				$insert->execute();
			} elseif ($row['access'] == 'hidden') {
				// copy the token to a public share
				// poll stays hidden for registered users
				$insert
				->setParameter('token', $row['token'])
				->setParameter('type', 'public')
				->setParameter('poll_id', $row['id'])
				->setParameter('user_id', null)
				->setParameter('user_email', null);
				$insert->execute();
			} elseif ($row['access'] == 'registered') {
				// copy the token to a public share
				// to keep the token
				$insert
				->setParameter('token', $row['token'])
				->setParameter('type', 'public')
				->setParameter('poll_id', $row['id'])
				->setParameter('user_id', null)
				->setParameter('user_email', null);
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
					->setParameter('user_email', null);
					$insert->execute();
				}
			}
		}
		$result->closeCursor();
	}

}
