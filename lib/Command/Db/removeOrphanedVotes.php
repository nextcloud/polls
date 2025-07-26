<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Command\Command;
use OCA\Polls\Db\IndexManager;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class removeOrphanedVotes extends Command {
	protected string $name = parent::NAME_PREFIX . 'index:create';
	protected string $description = 'Add all indices and foreign key constraints';
	protected array $operationHints = [
		'Adds indices and foreing key constraints.',
		'NO data migration will be executed, so make sure you have a backup of your database.',
	];

	public function __construct(
		private IndexManager $indexManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
		parent::__construct();
	}

	protected function runCommands(): int {
		// create indices and constraints
		// secure, that the schema is updated to the current status
		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);
		$this->addForeignKeyConstraints();
		$this->addIndices();
		$this->connection->migrateToSchema($this->schema);

		return 0;
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function addForeignKeyConstraints(): void {
		$this->printComment('Add foreign key constraints');
		$messages = $this->indexManager->createForeignKeyConstraints();
		$this->printInfo($messages, ' - ');
	}

	/**
	 * Create index for $table
	 */
	private function addIndices(): void {
		$this->printComment('Add indices');
		$messages = $this->indexManager->createIndices();
		$this->printInfo($messages, ' - ');
	}
}
