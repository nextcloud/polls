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

use OCA\Polls\Db\Poll;
use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Installation class for the polls app.
 * Initial db creation
 * Changed class naming: Version[jjmmpp]Date[YYYYMMDDHHMMSS]
 * Version: jj = major version, mm = minor, pp = patch
 */
class Version041000Date20221221070000 extends SimpleMigrationStep {
	public function __construct(
		protected IDBConnection $connection,
		protected IConfig $config,
		protected FixVotes $fixVotes
	) {
	}

	/**
	 * $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable(Poll::TABLE)) {
			// Call initial migration from class TableSchema
			// Drop old tables, which are migrated in prior versions
			foreach (TableSchema::removeObsoleteTables($schema) as $message) {
				$output->info('Polls - ' . $message);
			};

			// Drop old columns, which are migrated in prior versions
			foreach (TableSchema::removeObsoleteColumns($schema) as $message) {
				$output->info('Polls - ' . $message);
			};
		}

		// Create tables, as defined in TableSchema or fix column definitions
		foreach (TableSchema::createOrUpdateSchema($schema) as $message) {
			$output->info('Polls - ' . $message);
		};

		// remove old migration entries from versions prior to polls 3.x
		// including migration versions from test releases
		// theoretically, only this migration should be existent. If not, no matter
		foreach (TableSchema::removeObsoleteMigrations($this->connection) as $message) {
			$output->info('Polls - ' . $message);
		};

		$this->fixVotes->run($output);

		return $schema;
	}
}
