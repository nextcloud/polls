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

use Doctrine\DBAL\Schema\Schema;
use OCA\Polls\Command\Command;
use OCA\Polls\Db\IndexManager;
use OCP\IDBConnection;

class RemoveIndices extends Command {
	protected string $name = parent::NAME_PREFIX . 'index:remove';
	protected string $description = 'Remove all indices and foreign key constraints';
	protected array $operationHints = [
		'Removes all indices and foreign key constraints.',
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
		// remove constraints and indices
		$this->schema = $this->connection->createSchema();
		$this->indexManager->setSchema($this->schema);
		$this->deleteForeignKeyConstraints();
		$this->deleteGenericIndices();
		$this->deleteUniqueIndices();
		$this->connection->migrateToSchema($this->schema);
		return 0;
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteForeignKeyConstraints(): void {
		$this->printComment('Remove foreign key constraints and generic indices');
		$messages = $this->indexManager->removeAllForeignKeyConstraints();
		$this->printInfo($messages, ' - ');
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteGenericIndices(): void {
		$this->printComment('Remove generic indices');
		$messages = $this->indexManager->removeAllGenericIndices();
		$this->printInfo($messages, ' - ');
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteUniqueIndices(): void {
		$this->printComment('Remove unique indices');
		$messages = $this->indexManager->removeAllUniqueIndices();
		$this->printInfo($messages, ' - ');
	}
}
