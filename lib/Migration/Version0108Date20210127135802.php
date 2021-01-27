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

class Version0108Date20210127135802 extends SimpleMigrationStep {

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
		if (!$schema->hasTable('polls_watch')) {
			$table = $schema->createTable('polls_watch');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('table', 'string', [
				'length' => 64,
				'notnull' => true,
				'default' => ''
			]);
			$table->addColumn('poll_id', 'integer', [
				'length' => 11,
				'notnull' => true,
				'default' => 0
			]);
			$table->addColumn('updated', 'integer', [
				'length' => 11,
				'notnull' => true,
				'default' => 0
			]);
			$table->setPrimaryKey(['id']);
		} else {
			$table = $schema->getTable('polls_watch');
		}

		if (!$table->hasIndex('UNIQ_watch')) {
			$table->addUniqueIndex(['poll_id', 'table'], 'UNIQ_watch');
		}
		return $schema;
	}
}
