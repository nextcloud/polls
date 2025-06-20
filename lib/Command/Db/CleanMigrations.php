<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Command\Command;
use OCA\Polls\Db\TableManager;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class CleanMigrations extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:clean-migrations';
	protected string $description = 'Remove old migrations entries from Nextcloud\'s migration table';
	protected array $operationHints = [
		'All polls tables will get checked against the current schema.',
		'NO data migration will be executed, so make sure you have a backup of your database.',
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
		$this->tableManager->removeObsoleteMigrations();

		$this->printComment('Remove migration entries from migration table');
		$this->connection->migrateToSchema($this->schema);

		return 0;
	}
}
