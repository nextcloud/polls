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

use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCA\Polls\Migration\TableSchema;
use OCA\Polls\Migration\CreateIndices as IndexManagerCreate;
use OCA\Polls\Migration\RemoveIndices as IndexManagerRemove;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Doctrine\DBAL\Types\Type;

class Rebuild extends Command {
	public function __construct(
		private Connection $connection,
		private IndexManagerCreate $indexManagerCreate,
		private IndexManagerRemove $indexManagerRemove

	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('polls:db:rebuild')
			->setDescription('Rebuilds poll\'s table structure');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($this->requestConfirmation($input, $output)) {
			return 1;
		}

		// remove constraints and indices
		$this->deleteForeignKeyConstraints($output);
		$this->deleteGenericIndices($output);
		$this->deleteUniqueIndices($output);
		$this->indexManagerRemove->migrate();
		
		// remove old tables and columns
		$this->removeObsoleteTables($output);
		$this->removeObsoleteColumns($output);
		
		// validate and fix/create current table layout
		$this->createOrUpdateSchema($output);
		
		// recreate indices and constraints
		$this->addForeignKeyConstraints($output);
		$this->addIndices($output);
		$this->indexManagerCreate->migrate();
		
		return 0;
	}

	private function requestConfirmation(InputInterface $input, OutputInterface $output): int {
		if ($input->isInteractive()) {
			$helper = $this->getHelper('question');
			$output->writeln('<comment>All polls tables will get checked against the current schema.</comment>');
			$output->writeln('<comment>NO data migration will be executed, so make sure you have a backup of your database.</comment>');
			$output->writeln('');

			$question = new ConfirmationQuestion('Continue with the conversion (y/n)? [n] ', false);
			if (!$helper->ask($input, $output, $question)) {
				return 1;
			}
		}
		return 0;
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function addForeignKeyConstraints(OutputInterface $output): void {
		$output->writeln('<comment>Add foreign key constraints</comment>');
		$messages = $this->indexManagerCreate->createForeignKeyConstraints();

		foreach ($messages as $message) {
			$output->writeln('<info> ' . $message . ' </info>');
		}
	}



	/**
	 * Create index for $table
	 */
	private function addIndices(OutputInterface $output): void {
		$output->writeln('<comment>Add indices</comment>');
		$messages = $this->indexManagerCreate->createIndices();
		foreach ($messages as $message) {
			$output->writeln('<info> ' . $message . ' </info>');
		}
	}

	/**
	 * Iterate over tables and make sure, the are created or updated
	 * according to the schema
	 */
	private function createOrUpdateSchema(OutputInterface $output): void {
		$output->writeln('<comment>Set db structure</comment>');
		$schema = new SchemaWrapper($this->connection);

		foreach (TableSchema::TABLES as $tableName => $columns) {
			$tableCreated = false;

			if ($schema->hasTable($tableName)) {
				$output->writeln(' - Validating table ' . $tableName);
				$table = $schema->getTable($tableName);
			} else {
				$output->writeln('<info> - Creating table ' . $tableName . '</info>');
				$table = $schema->createTable($tableName);
				$tableCreated = true;
			}

			foreach ($columns as $columnName => $columnDefinition) {
				if ($table->hasColumn($columnName)) {
					$column = $table->getColumn($columnName);
					$column->setOptions($columnDefinition['options']);
					if ($column->getType()->getName() !== $columnDefinition['type']) {
						$output->writeln('<info>   Migrated type of ' . $tableName . '[\'' . $columnName . '\'] from ' . $column->getType()->getName() . ' to ' . $columnDefinition['type'] . '</info>');
						$column->setType(Type::getType($columnDefinition['type']));
					}

					// force change to current options definition
					$table->changeColumn($columnName, $columnDefinition['options']);
				} else {
					$table->addColumn($columnName, $columnDefinition['type'], $columnDefinition['options']);
					$output->writeln('<info>  Added ' . $tableName . ', ' . $columnName . ' (' . $columnDefinition['type'] . ')</info>');
				}
			}

			if ($tableCreated) {
				$table->setPrimaryKey(['id']);
			}
		}

		$this->connection->migrateToSchema($schema->getWrappedSchema());
	}

	private function removeObsoleteColumns(OutputInterface $output): void {
		$output->writeln('<comment>Drop orphaned columns</comment>');
		$schema = new SchemaWrapper($this->connection);
		$dropped = false;

		foreach (TableSchema::GONE_COLUMNS as $tableName => $columns) {
			if ($schema->hasTable($tableName)) {
				$table = $schema->getTable($tableName);

				foreach ($columns as $columnName) {
					if ($table->hasColumn($columnName)) {
						$dropped = true;
						$table->dropColumn($columnName);
						$output->writeln('<info> - Dropped ' . $columnName . ' from ' . $tableName .'</info>');
					}
				}
			}
		}

		$this->connection->migrateToSchema($schema->getWrappedSchema());

		if (!$dropped) {
			$output->writeln(' - No orphaned columns found');
		}
	}

	/**
	 * Remove obsolete tables if they still exist
	 */
	private function removeObsoleteTables(OutputInterface $output): void {
		$output->writeln('<comment>Drop orphaned tables</comment>');
		$schema = new SchemaWrapper($this->connection);
		$dropped = false;

		foreach (TableSchema::GONE_TABLES as $tableName) {
			if ($schema->hasTable($tableName)) {
				$dropped = true;
				$schema->dropTable($tableName);
				$output->writeln('<info> - Dropped ' . $tableName .'</info>');
			}
		}

		$this->connection->migrateToSchema($schema->getWrappedSchema());

		if (!$dropped) {
			$output->writeln(' - No orphaned tables found');
		}
	}

	private function deleteForeignKeyConstraints(OutputInterface $output): void {
		$output->writeln('<comment>Remove foreign key constraints and generic indices</comment>');
		$messages = $this->indexManagerRemove->removeAllForeignKeyConstraints();

		foreach ($messages as $message) {
			$output->writeln('<info> ' . $message . ' </info>');
		}
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteGenericIndices(OutputInterface $output): void {
		$output->writeln('<comment>Remove generic indices</comment>');
		$messages = $this->indexManagerRemove->removeAllGenericIndices();

		foreach ($messages as $message) {
			$output->writeln('<info> ' . $message . ' </info>');
		}
	}

	/**
	 * add an on delete fk contraint to all tables referencing the main polls table
	 */
	private function deleteUniqueIndices(OutputInterface $output): void {
		$output->writeln('<comment>Remove unique indices</comment>');
		$messages = $this->indexManagerRemove->removeAllUniqueIndices();

		foreach ($messages as $message) {
			$output->writeln('<info> ' . $message . ' </info>');
		}
	}
}
