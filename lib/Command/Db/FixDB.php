<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Command\Command;
use OCA\Polls\Db\V3\TableManager;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class FixDB extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:fix';
	protected string $description = 'Fix poll\'s table structure';
	protected array $operationHints = [
		'All polls tables will get checked and eventually updated against the current schema.',
		'',
		'Idices will not get updated, created or removed.',
		'Note: NO data migration will be executed, so make sure you have a backup of your database.',
	];

	public function __construct(
		private TableManager $tableManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
		parent::__construct();
	}

	protected function runCommands(): int {
		$this->schema = $this->connection->createSchema();
		$this->tableManager->setSchema($this->schema);

		$this->createOrUpdateSchema();

		$this->connection->migrateToSchema($this->schema);

		return 0;
	}

	/**
	 * Iterate over tables and make sure, the are created or updated
	 * according to the schema
	 */
	private function createOrUpdateSchema(): void {
		$this->printComment(' - Set db structure');
		$messages = $this->tableManager->createTables();
		$this->printInfo($messages, '   ');
	}
}
