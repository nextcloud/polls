<?php

declare(strict_types=1);
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

namespace OCA\Polls\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Command\Command;
use OCA\Polls\Db\IndexManager;
use OCA\Polls\Db\TableManager;
use OCA\Polls\Db\Watch;
use OCA\Polls\Migration\TableSchema;
use OCP\IDBConnection;

class ResetWatch extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:reset-watch';
	protected string $description = 'Resets the Watch table';
	protected array $operationHints = [
		'All polls tables will get checked against the current schema.',
		'NO data migration will be executed, so make sure you have a backup of your database.',
	];

	public function __construct(
		private IndexManager $indexManager,
		private TableManager $tableManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
		parent::__construct();
	}

	protected function runCommands(): int {
		$tableName = Watch::TABLE;
		$indexValues = TableSchema::UNIQUE_INDICES[$tableName];
		$columns = TableSchema::TABLES[$tableName];

		$messages = $this->tableManager->removeWatch();
		$this->printInfo($messages, ' - ');

		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);
		$this->tableManager->setSchema($this->schema);

		$messages = $this->tableManager->createTable($tableName, $columns);
		$messages[] = $this->indexManager->createIndex($tableName, $indexValues['name'], $indexValues['columns'], $indexValues['unique']);

		$this->connection->migrateToSchema($this->schema);

		$this->printInfo($messages, ' - ');
		return 0;
	}

}
