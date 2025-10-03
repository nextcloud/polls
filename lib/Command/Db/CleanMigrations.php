<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Command\Command;
use OCA\Polls\Db\V5\TableManager;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class CleanMigrations extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:clean-migrations';
	protected string $description = 'Remove old migrations entries from Nextcloud\'s migration table';
	protected array $operationHints = [
		'This command will remove all entries from the Nextcloud migration table that are related to the Polls app.',
		'*****************************',
		'**    Please understand    **',
		'*****************************',
		'Although old migrations entries are not used anymore, they can still remain in the installation, based on the way polls got installed.',
		'This could result in executing invalid migrations in the future, which then could result in a unpredictable database state.',
		'Therefore this command is highly NOT RECOMMENDED and should only be executed if you know what you are doing.',
		'',
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
		$this->tableManager->removeObsoleteMigrations();

		$this->printComment('Remove migration entries from migration table');
		$this->connection->migrateToSchema($this->schema);

		return 0;
	}
}
