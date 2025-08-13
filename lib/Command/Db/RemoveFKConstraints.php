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
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @psalm-api
 */
class RemoveFKConstraints extends Command {
	protected string $name = parent::NAME_PREFIX . 'index:remove:foreign-key-constraints';
	protected string $description = 'Remove all foreign key constraints';
	protected array $operationHints = [
		'Remove all foreign key constraints.',
		'*****************************',
		'**    Please understand    **',
		'*****************************',
		'This can lead to inconsitent database states, because it affects the database integrity.',
		'Therefoe this is highly NOT RECOMMENDED and should only be used if you know what you are doing.',
		'',
		'To recreate the Foreign key constraints, run the command \'occ ' . parent::NAME_PREFIX . 'index:create\'',
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
		$this->deleteForeignKeyConstraints();
		$this->deleteGenericIndices();
		$this->connection->migrateToSchema($this->schema);
		return 0;
	}

	/**
	 * remove all generic indices (the only generic indices should
	 * result from the FK Constraints)
	 */
	private function deleteGenericIndices(): void {
		$this->printComment('Remove generic indices');
		$messages = $this->indexManager->removeAllGenericIndices();
		$this->printInfo($messages, ' - ');
	}

	/**
	 * remove on delete fk contraint from all tables referencing the main polls table
	 */
	private function deleteForeignKeyConstraints(): void {
		$this->printComment('Remove foreign key constraints and generic indices');
		$messages = $this->indexManager->removeAllForeignKeyConstraints();
		$this->printInfo($messages, ' - ');
	}
}
