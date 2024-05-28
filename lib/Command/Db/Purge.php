<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Db;

use OCA\Polls\Command\Command;
use OCA\Polls\Db\TableManager;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class Purge extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:purge';
	protected string $description = 'Remove all polls related tables and records';
	protected array $operationHints = [
		'This command will remove Polls completely from your instance',
		' - delete all oc_polls_* tables, ',
		' - delete Polls\'s migration records from oc_migrations, ',
		' - delete Polls\'s app config records from oc_appconfig.',
		' ',
		'after running this command call \'occ app:remove polls \'',
	];

	public function __construct(
		private IDBConnection $connection,
		private TableManager $tableManager
	) {
		parent::__construct();
	}

	protected function runCommands(): int {
		$this->tableManager->setConnection($this->connection);
		$messages = $this->tableManager->purgeTables();
		$this->printInfo($messages, ' - ');
		return 0;
	}
}
