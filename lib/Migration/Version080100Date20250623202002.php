<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Migration;

use Doctrine\DBAL\Types\Type;
use OCA\Polls\Db\TableManager;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Installation class for the polls app.
 * Initial db creation
 * Changed class naming: Version[jjmmpp]Date[YYYYMMDDHHMMSS]
 * Version: jj = major version, mm = minor, pp = patch
 *
 * @psalm-suppress UnusedClass
 */
class Version080100Date20250623202002 extends SimpleMigrationStep {
	private ISchemaWrapper $schema;

	public function __construct(
		private TableManager $tableManager,
		private IDBConnection $connection,
	) {
	}

	/**
	 * $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		$this->schema = $schemaClosure();
		$messages = $this->createTables();

		foreach ($messages as $message) {
			$output->info('Polls - ' . $message);
		};

		return $this->schema;
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return non-empty-list<string>
	 */
	public function createTable(string $tableName, array $columns): array {
		$messages = [];

		if ($this->schema->hasTable($tableName)) {
			$table = $this->schema->getTable($tableName);
			$messages[] = 'Validating table ' . $table->getName();
			$tableCreated = false;
		} else {
			$table = $this->schema->createTable($tableName);
			$tableCreated = true;
			$messages[] = 'Creating table ' . $table->getName();
		}

		foreach ($columns as $columnName => $columnDefinition) {
			if ($table->hasColumn($columnName)) {
				$column = $table->getColumn($columnName);
				if (Type::lookupName($column->getType()) !== $columnDefinition['type']) {
					$messages[] = 'Migrated type of ' . $table->getName() . '[\'' . $columnName . '\'] from ' . Type::lookupName($column->getType()) . ' to ' . $columnDefinition['type'];
					$column->setType(Type::getType($columnDefinition['type']));
				}
				$column->setOptions($columnDefinition['options']);

				// force change to current options definition
				$table->modifyColumn($columnName, $columnDefinition['options']);
			} else {
				$table->addColumn($columnName, $columnDefinition['type'], $columnDefinition['options']);
				$messages[] = 'Added ' . $table->getName() . ', ' . $columnName . ' (' . $columnDefinition['type'] . ')';
			}
		}

		if ($tableCreated) {
			$table->setPrimaryKey(['id']);
		}
		return $messages;
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return non-empty-list<string>
	 */
	public function createTables(): array {
		$messages = [];

		foreach (TableSchema::TABLES as $tableName => $columns) {
			$messages = array_merge($messages, $this->createTable($tableName, $columns));
		}
		return $messages;
	}
}
