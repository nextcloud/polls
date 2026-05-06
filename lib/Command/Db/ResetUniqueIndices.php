<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Command\Command;
use OCA\Polls\Db\V10\IndexManager;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class ResetUniqueIndices extends Command {
	protected string $name = parent::NAME_PREFIX . 'db:reset-unique-indices';
	protected string $description = '[TEST] Drop all unique indices and recreate them with named UNIQ_ prefix';
	protected array $operationHints = [
		'For testing purposes only.',
		'Drops all unique indices from Polls tables and recreates them with explicit UNIQ_ names.',
		'This simulates the pre-migration state to verify that the repair step handles existing named indices correctly.',
		'Note: This triggers a full re-indexing and can be time consuming on large installations.',
	];

	public function __construct(
		private IndexManager $indexManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
		parent::__construct();
	}

	protected function runCommands(): int {
		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);

		$this->printComment('Dropping all unique indices');
		$messages = $this->indexManager->removeAllUniqueIndices();
		$this->printInfo($messages, ' - ');
		$this->connection->migrateToSchema($this->schema);

		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);

		$this->printComment('Recreating unique indices with UNIQ_ names');
		$messages = $this->indexManager->createUniqueIndices(true);
		$this->printInfo($messages, ' - ');
		$this->connection->migrateToSchema($this->schema);

		$this->printComment('Existing indices after reset:');
		$messages = $this->indexManager->listExistingIndices();
		$this->printInfo($messages, ' - ');

		return 0;
	}
}
