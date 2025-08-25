<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Command\Command;
use OCA\Polls\Db\V3\IndexManager;
use OCA\Polls\Db\V3\TableManager;
use OCA\Polls\Db\Watch;
use OCA\Polls\Migration\V3\TableSchema;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class ResetWatch extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:reset-watch';
	protected string $description = 'Resets the Watch table';
	protected array $operationHints = [
		'Removes and recreates the watch table to set ids to zero',
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

		$messages = $this->tableManager->removeWatch();
		$this->printInfo($messages, ' - ');

		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);
		$this->tableManager->setSchema($this->schema);

		$messages = $this->tableManager->createTable($tableName);

		foreach (TableSchema::UNIQUE_INDICES[$tableName] as $name => $definition) {
			$messages[] = $this->indexManager->createIndex($tableName, $name, $definition['columns'], true);
		}

		$this->connection->migrateToSchema($this->schema);

		$this->printInfo($messages, ' - ');
		return 0;
	}

}
