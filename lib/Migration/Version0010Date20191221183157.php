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
class Version0010Date20191221183157 extends SimpleMigrationStep {

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

		if (!$schema->hasTable('polls_log')) {
			$table = $schema->createTable('polls_log');
			$table->addColumn('id', Type::INTEGER, [
				'autoincrement' => true,
				'notnull' => true
			]);
			$table->addColumn('created', Type::INTEGER, [
				'notnull' => true,
				'length' => 11,
				'default' => 0
			]);
			$table->addColumn('processed', Type::INTEGER, [
				'notnull' => true,
				'length' => 11,
				'default' => 0
			]);
			$table->addColumn('poll_id', Type::INTEGER, [
				'notnull' => true
			]);
			$table->addColumn('user_id', Type::STRING, [
				'notnull' => false,
				'length' => 1024
			]);
			$table->addColumn('display_name', Type::STRING, [
				'notnull' => false,
				'length' => 64
			]);
			$table->addColumn('message_id', Type::STRING, [
				'notnull' => false,
				'length' => 64
			]);
			$table->addColumn('message', Type::STRING, [
				'notnull' => false,
				'length' => 1024
			]);
			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}

}
