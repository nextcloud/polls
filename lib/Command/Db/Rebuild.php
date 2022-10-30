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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Rebuild extends Command {
    /** @var Connection */
	private $connection;

	public function __construct(Connection $connection) {
		parent::__construct();
        $this->connection = $connection;
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
		$this->removeForeignKeys($output);
		$this->removeGenericIndices($output);
		$this->removeUniqueIndices($output);

		// remove old tables and columns
		$this->removeObsoleteTables($output);
		$this->removeObsoleteColumns($output);

		// validate and fix/create current table layout
		$this->createOrUpdateSchema($output);

		// recreate indices and constraints
		$this->createIndices($output);
		$this->createForeignKeyConstraints($output);

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
	private function createForeignKeyConstraints(OutputInterface $output): void {
		$output->writeln('<comment>Add foreign key constraints</comment>');
		$schema = new SchemaWrapper($this->connection);

		$parentTable = $schema->getTable(TableSchema::FK_PARENT_TABLE);

		foreach (TableSchema::FK_CHILD_TABLES as $childTable) {
			$table = $schema->getTable($childTable);
			$table->addForeignKeyConstraint($parentTable, ['poll_id'], ['id'], ['onDelete' => 'CASCADE']);
			$output->writeln('<info> - Created ' . TableSchema::FK_PARENT_TABLE . '[\'poll_id\'] <- ' . $childTable . '[\'id\'] </info>');
		}
		$this->connection->migrateToSchema($schema->getWrappedSchema());
	}



	/**
	 * Create index for $table
	 */
	private function createIndices(OutputInterface $output): void {
		$output->writeln('<comment>Add indices</comment>');
		$schema = new SchemaWrapper($this->connection);

		foreach (TableSchema::UNIQUE_INDICES as $tableName => $values) {
			if ($schema->hasTable($tableName)) {
				$table = $schema->getTable($tableName);
				if (!$table->hasIndex($values['name'])) {
					$table->addUniqueIndex($values['columns'], $values['name']);
					$output->writeln('<info> - Added unique index ' . $values['name'] . ' to ' . $tableName . '</info>');

					// TODO: Commented out atm to get psalm quiet, because we just have unique indices.
					//
					// if ($values['unique']) {
					// 	$table->addUniqueIndex($values['columns'], $values['name']);
					// 	$output->writeln('<info> - Added unique index ' . $values['name'] . ' to ' . $tableName . '</info>');
					// } else {
					// 	$table->addIndex($values['columns'], $values['name']);
					// 	$output->writeln('<info> - Added index ' . $values['name'] . ' to ' . $tableName . '</info>');
					// }
				}
			}
		}
		$this->connection->migrateToSchema($schema->getWrappedSchema());
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
						$column->setType($columnDefinition['type']);
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

	/**
	 * remove all UNIQUE indices from $table
	 */
	private function removeUniqueIndices(OutputInterface $output): void {
		$output->writeln('<comment>Remove unique indices</comment>');
		$schema = new SchemaWrapper($this->connection);

		foreach (TableSchema::UNIQUE_INDICES as $tableName => $value) {
			if ($schema->hasTable($tableName)) {
				$table = $schema->getTable($tableName);

				foreach ($table->getIndexes() as $index) {
					if (strpos($index->getName(), 'UNIQ_') === 0) {
						$this->removeIndex($schema, $tableName, $index->getName());
						$output->writeln('<info> - Removed ' . $index->getName() . ' from '. $tableName .'</info>');
					}
				}
			}
		}
		$this->connection->migrateToSchema($schema->getWrappedSchema());
	}

	/**
	 * remove all UNIQUE indices from $table
	 */
	private function removeGenericIndices(OutputInterface $output): void {
		$output->writeln('<comment>Remove indices</comment>');
		$schema = new SchemaWrapper($this->connection);

		foreach (TableSchema::FK_CHILD_TABLES as $tableName) {
			if ($schema->hasTable($tableName)) {
				$table = $schema->getTable($tableName);

				foreach ($table->getIndexes() as $index) {
					if (strpos($index->getName(), 'IDX_') === 0) {
						$this->removeIndex($schema, $tableName, $index->getName());
						$output->writeln('<info> - Removed ' . $index->getName() . ' from '. $tableName .'</info>');
					}
				}

			}
		}

		$this->connection->migrateToSchema($schema->getWrappedSchema());
	}

	private function removeIndex(SchemaWrapper $schema, string $tableName, string $indexName): void {
		if ($schema->hasTable($tableName)) {
			$table = $schema->getTable($tableName);
			if ($table->hasIndex($indexName)) {
				$table->dropIndex($indexName);
			}
		}
	}

	/**
	 * 	remove all foreign keys from $tableName
	 */
	private function removeForeignKeys(OutputInterface $output): void {
		$output->writeln('<comment>Remove foreign key constraints</comment>');
		$schema = new SchemaWrapper($this->connection);

		foreach (TableSchema::FK_CHILD_TABLES as $tableName) {
			if ($schema->hasTable($tableName)) {
				$table = $schema->getTable($tableName);

				foreach ($table->getForeignKeys() as $foreignKey) {
					$this->removeForeignKey($schema, $tableName, $foreignKey->getName());
					$output->writeln('<info> - Remove ' . $foreignKey->getName() . ' from '. $tableName .'</info>');
				}
			}
		}
		$this->connection->migrateToSchema($schema->getWrappedSchema());
	}

	/**
	 * remove a foreign key with $foreignKeyName from $tableName
	 */
	private function removeForeignKey(SchemaWrapper $schema, string $tableName, string $foreignKeyName): void {
		if ($schema->hasTable($tableName)) {
			$table = $schema->getTable($tableName);
			$table->removeForeignKey($foreignKeyName);
		}
	}
}
