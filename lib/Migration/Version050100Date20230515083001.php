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
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Migration;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\TableManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Installation class for the polls app.
 * Initial db creation
 * Changed class naming: Version[jjmmpp]Date[YYYYMMDDHHMMSS]
 * Version: jj = major version, mm = minor, pp = patch
 */
class Version050100Date20230515083001 extends SimpleMigrationStep {
	public function __construct(
		private TableManager $tableManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
	}

	/**
	 * $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		// Create tables, as defined in TableSchema or fix column definitions
		$this->schema = $this->connection->createSchema();
		$this->tableManager->setSchema($this->schema);

		$messages = $this->tableManager->createTables();

		$this->connection->migrateToSchema($this->schema);

		foreach ($messages as $message) {
			$output->info('Polls - ' . $message);
		};

		return null;
	}

	/**
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$now = time();
		$query = $this->connection->getQueryBuilder();
		$query->update(Poll::TABLE)
			->set('last_interaction', $query->createNamedParameter($now))
			->where($query->expr()->eq('last_interaction', $query->createNamedParameter(0)));
		$query->executeStatement();
	}
}
