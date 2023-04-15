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

use OCA\Polls\Db\IndexManager;
use OCA\Polls\Db\TableManager;
use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Installation class for the polls app.
 * Initial db creation
 * Changed class naming: Version[jjmmpp]Date[YYYYMMDDHHMMSS]
 * Version: jj = major version, mm = minor, pp = patch
 */
class Version040102Date20230123072601 extends SimpleMigrationStep {
	public function __construct(
		private IDBConnection $connection,
		private IConfig $config,
		private FixVotes $fixVotes,
		private IndexManager $indexManager,
		private TableManager $tableManager
	) {
	}

	/**
	 * $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		// Create tables, as defined in TableSchema or fix column definitions
		foreach ($this->tableManager->createTables() as $message) {
			$output->info('Polls - ' . $message);
		};
		$this->tableManager->migrate();

		$this->indexManager->refreshSchema();
		$this->indexManager->createForeignKeyConstraints();
		$this->indexManager->createIndices();
		$this->indexManager->migrate();

		return $schema;
	}
}
