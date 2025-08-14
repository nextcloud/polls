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
class RemoveOptionalIndices extends Command {
	protected string $name = parent::NAME_PREFIX . 'index:remove:optional';
	protected string $description = 'Remove all optional indices';
	protected array $operationHints = [
		'Removes all optional indices. Removing them may decrease your database query performance.',
		'To recreate optional indices, run the command \'occ db:add-missing-indices\'',
		'Note: NO data migration will be executed, so make sure you have a backup of your database.',
	];

	public function __construct(
		private IndexManager $indexManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
		parent::__construct();
	}

	protected function runCommands(): int {
		// remove constraints and indices
		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);
		$this->deleteNamedIndices();
		$this->connection->migrateToSchema($this->schema);
		return 0;
	}

	/**
	 * remove all named indices
	 */
	private function deleteNamedIndices(): void {
		$this->printComment('Remove optional indices');
		$messages = $this->indexManager->removeNamedIndices();
		$this->printInfo($messages, ' - ');
	}
}
