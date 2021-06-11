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

use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version0109Date20210611164500 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	/** @var IConfig */
	protected $config;

	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}

	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {

		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('polls_polls')) {
			$table = $schema->getTable('polls_polls');
			if ($table->hasColumn('allow_proposals')) {
				$table->dropColumn('allow_proposals');
			}
			if ($table->hasColumn('use_no')) {
				$table->dropColumn('use_no');
			}
			if ($table->hasColumn('proposals_expire')) {
				$table->dropColumn('proposals_expire');
			}
		}

		if ($schema->hasTable('polls_options')) {
			$table = $schema->getTable('polls_options');
			if ($table->hasColumn('owner')) {
				$table->dropColumn('owner');
			}

			if (!$table->hasColumn('released')) {
				$table->dropColumn('released');
			}
		}

		return $schema;
	}
}
