<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Migration;

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Db\V4\TableManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * @psalm-suppress UnusedClass
 */
class FixVotes implements IRepairStep {
	public function __construct(
		private TableManager $tableManager,
		private IDBConnection $connection,
		private Schema $schema,
	) {
	}

	/*
	 * @inheritdoc
	 */
	public function getName() {
		return 'Polls repairstep - Fix votes with duration options';
	}

	/*
	 * @inheritdoc
	 */
	public function run(IOutput $output): void {
		// secure, that the schema is updated to the current status
		$this->schema = $this->connection->createSchema();
		$this->tableManager->setSchema($this->schema);
		$this->tableManager->fixVotes();
		$this->connection->migrateToSchema($this->schema);
	}
}
