<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Command\Db;

use OCA\Polls\Db\TableManager;
use OCA\Polls\Db\IndexManager;
use OCA\Polls\Command\Command;

class Rebuild extends Command {
	/** @var IndexManager */
	private $indexManager;

	/** @var TableManager */
	private $tableManager;

	/** @var string */
	protected $name = self::NAME_PREFIX . 'db:rebuild';

	/** @var string */
	protected $description = 'Rebuilds poll\'s table structure';

	public function __construct(
		TableManager $tableManager,
		IndexManager $indexManager,
	) {
		parent::__construct();
		$this->indexManager = $indexManager;
		$this->tableManager = $tableManager;
	}

	protected function runCommands(): int {
		// remove constraints and indices
		$this->printComment('Step 1. Remove all indices and foreign key constraints');
		// secure, that the schema is updated to the current status
		$this->indexManager->refreshSchema();
		$this->deleteForeignKeyConstraints();
		$this->deleteGenericIndices();
		$this->deleteUniqueIndices();
		$this->indexManager->migrate();

		// remove old tables and columns
		$this->printComment('Step 2. Remove all orphaned tables and columns');
		// secure, that the schema is updated to the current status
		$this->tableManager->refreshSchema();
		$this->removeObsoleteTables();
		$this->removeObsoleteColumns();
		$this->tableManager->migrate();

		// validate and fix/create current table layout
		$this->printComment('Step 3. Create or update tables to current shema');
		$this->createOrUpdateSchema();
		$this->tableManager->migrate();
		
		// recreate indices and constraints
		$this->printComment('Step 4. Recreate indices and foreign key constraints');
		// secure, that the schema is updated to the current status
		$this->indexManager->refreshSchema();
		$this->addForeignKeyConstraints();
		$this->addIndices();
		$this->indexManager->migrate();
		
		return 0;
	}

	protected function requestConfirmation(): int {
		if ($this->input->isInteractive()) {
			$helper = $this->getHelper('question');
			$this->printComment('All polls tables will get checked against the current schema.');
			$this->printComment('NO data migration will be executed, so make sure you have a backup of your database.');
			$this->printNewLine();

			if (!$helper->ask($this->input, $this->output, $this->question)) {
				return 1;
			}
		}
		return 0;
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function addForeignKeyConstraints(): void {
		$this->printComment('- Add foreign key constraints');
		$messages = $this->indexManager->createForeignKeyConstraints();

		foreach ($messages as $message) {
			$this->printInfo(' - ' . $message);
		}
	}

	/**
	 * Create index for $table
	 */
	private function addIndices(): void {
		$this->printComment('- Add indices');
		$messages = $this->indexManager->createIndices();
		foreach ($messages as $message) {
			$this->printInfo(' - ' . $message);
		}
	}

	/**
	 * Iterate over tables and make sure, the are created or updated
	 * according to the schema
	 */
	private function createOrUpdateSchema(): void {
		$this->printComment('- Set db structure');
		$messages = $this->tableManager->createTables();
		foreach ($messages as $message) {
			$this->printInfo(' - ' . $message);
		}
	}

	private function removeObsoleteColumns(): void {
		$this->printComment('- Drop orphaned columns');
		$messages = $this->tableManager->removeObsoleteColumns();
		foreach ($messages as $message) {
			$this->printInfo(' - ' . $message);
		}
	}

	/**
	 * Remove obsolete tables if they still exist
	 */
	private function removeObsoleteTables(): void {
		$this->printComment('  Drop orphaned tables');
		$messages = $this->tableManager->removeObsoleteTables();

		foreach ($messages as $message) {
			$this->printInfo(' - ' . $message);
		}
	}

	private function deleteForeignKeyConstraints(): void {
		$this->printComment('- Remove foreign key constraints');
		$messages = $this->indexManager->removeAllForeignKeyConstraints();

		foreach ($messages as $message) {
			$this->printInfo(' - ' . $message);
		}
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteGenericIndices(): void {
		$this->printComment('- Remove generic indices');
		$messages = $this->indexManager->removeAllGenericIndices();

		foreach ($messages as $message) {
			$this->printInfo(' - ' . $message);
		}
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteUniqueIndices(): void {
		$this->printComment('- Remove unique indices');
		$messages = $this->indexManager->removeAllUniqueIndices();

		foreach ($messages as $message) {
			$this->printInfo(' - ' . $message);
		}
	}
}
