<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Command\Db;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Command\Command;
use OCA\Polls\Db\V5\IndexManager;
use OCP\IDBConnection;

/**
 * @psalm-api
 */
class RemoveUniqueIndices extends Command {
	protected string $name = parent::NAME_PREFIX . 'index:remove:unique-indices';
	protected string $description = 'Remove all unique indices';
	protected array $operationHints = [
		'Removes all unique indices.',
		'*****************************',
		'**    Please understand    **',
		'*****************************',
		'This can lead to inconsitent database states, because the uniqueness of data is not guaranteed anymore.',
		'Therefore this command is highly NOT RECOMMENDED and should only be executed if you know what you are doing.',
		'',
		'To recreate the unique indices, run the command \'occ ' . parent::NAME_PREFIX . 'index:create\'',
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
		$this->deleteUniqueIndices();
		$this->connection->migrateToSchema($this->schema);
		return 0;
	}

	/**
	 * remove all unique indices
	 */
	private function deleteUniqueIndices(): void {
		$this->printComment('Remove unique indices');
		$messages = $this->indexManager->removeAllUniqueIndices();
		$this->printInfo($messages, ' - ');
	}
}
