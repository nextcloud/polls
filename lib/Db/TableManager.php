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
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Polls\Db;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use OCA\Polls\Migration\TableSchema;
use OCP\IDBConnection;
use OCP\IConfig;

class TableManager {
	/** @var IConfig */
	private $config;
	
	/** @var IDBConnection */
	protected $connection;

	/** @var Schema */
	private $schema;
	
	/** @var string */
	private $dbPrefix;

	/** @var LogMapper */
	private $logMapper;

	/** @var OptionMapper */
	private $optionMapper;

	/** @var PreferencesMapper */
	private $preferencesMapper;

	/** @var ShareMapper */
	private $shareMapper;

	/** @var SubscriptionMapper */
	private $subscriptionMapper;

	/** @var VoteMapper */
	private $voteMapper;

	/** @var WatchMapper */
	private $watchMapper;

	public function __construct(
		IConfig $config,
		IDBConnection $connection,
		LogMapper $logMapper,
		OptionMapper $optionMapper,
		PreferencesMapper $preferencesMapper,
		ShareMapper $shareMapper,
		SubscriptionMapper $subscriptionMapper,
		VoteMapper $voteMapper,
		WatchMapper $watchMapper
	) {
		$this->config = $config;
		$this->connection = $connection;
		$this->logMapper = $logMapper;
		$this->optionMapper = $optionMapper;
		$this->preferencesMapper = $preferencesMapper;
		$this->shareMapper = $shareMapper;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->voteMapper = $voteMapper;
		$this->watchMapper = $watchMapper;
		$this->schema = $this->connection->createSchema();
		$this->dbPrefix = $this->config->getSystemValue('dbtableprefix', 'oc_');
	}

	/**
	 * execute the migration
	 */
	public function migrate(): void {
		$this->connection->migrateToSchema($this->schema);
	}

	public function refreshSchema() {
		$this->schema = $this->connection->createSchema();
	}

	public function resetWatch() {
		$messages = [];
		$tableName = $this->dbPrefix . Watch::TABLE;
		$columns = TableSchema::TABLES[Watch::TABLE];

		if ($this->connection->tableExists(Watch::TABLE)) {
			$this->connection->dropTable(Watch::TABLE);
			$messages[] = 'Dropped ' . $tableName;
		}

		$this->refreshSchema();
		
		$table = $this->schema->createTable($tableName);
		$messages[] = 'Creating table ' . $tableName;

		foreach ($columns as $columnName => $columnDefinition) {
			$table->addColumn($columnName, $columnDefinition['type'], $columnDefinition['options']);
			$messages[] = 'Added ' . $tableName . ', ' . $columnName . ' (' . $columnDefinition['type'] . ')';
		}

		$table->setPrimaryKey(['id']);
		return $messages;
	}

	public function createTables() {
		$messages = [];
		
		foreach (TableSchema::TABLES as $tableName => $columns) {
			$tableName = $this->dbPrefix . $tableName;

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
					if ($column->getType()->getName() !== $columnDefinition['type']) {
						$messages[] = 'Migrated type of ' . $table->getName() . '[\'' . $columnName . '\'] from ' . $column->getType()->getName() . ' to ' . $columnDefinition['type'];
						$column->setType(Type::getType($columnDefinition['type']));
					}
					$column->setOptions($columnDefinition['options']);

					// force change to current options definition
					$table->changeColumn($columnName, $columnDefinition['options']);
				} else {
					$table->addColumn($columnName, $columnDefinition['type'], $columnDefinition['options']);
					$messages[] = 'Added ' . $table->getName() . ', ' . $columnName . ' (' . $columnDefinition['type'] . ')';
				}
			}

			if ($tableCreated) {
				$table->setPrimaryKey(['id']);
			}
		}
		return $messages;
	}

	/**
	 * Remove obsolete tables if they still exist
	 */
	public function removeObsoleteTables(): array {
		$dropped = false;

		foreach (TableSchema::GONE_TABLES as $tableName) {
			// $tableName = $this->dbPrefix . $tableName;
			if ($this->connection->tableExists($tableName)) {
				$dropped = true;
				$this->connection->dropTable($tableName);
				$messages[] = 'Dropped ' . $this->dbPrefix . $tableName;
			}
		}

		if (!$dropped) {
			$messages[] = 'No orphaned tables found';
		}
		return $messages;
	}

	/**
	 * Remove obsolete tables if they still exist
	 */
	public function removeWatch(): array {
		$tableName = Watch::TABLE;
		$messages = [];
		if ($this->connection->tableExists($tableName)) {
			$this->connection->dropTable($tableName);
			$messages[] = 'Dropped ' . $this->dbPrefix . $tableName;
		}
		return $messages;
	}

	public function removeObsoleteColumns(): array {
		$messages = [];
		$dropped = false;

		foreach (TableSchema::GONE_COLUMNS as $tableName => $columns) {
			$tableName = $this->dbPrefix . $tableName;
			if ($this->schema->hasTable($tableName)) {
				$table = $this->schema->getTable($tableName);

				foreach ($columns as $columnName) {
					if ($table->hasColumn($columnName)) {
						$dropped = true;
						$table->dropColumn($columnName);
						$messages[] = 'Dropped ' . $columnName . ' from ' . $tableName;
					}
				}
			}
		}

		if (!$dropped) {
			$messages[] = 'No orphaned columns found';
		}

		return $messages;
	}

	/**
	 * delete all orphaned entries by selecting all rows
	 * those poll_ids are not present in the polls table
	 *
	 * we have to use a raw query, because NOT EXISTS is not
	 * part of doctrine's expression builder
	 */
	public function removeOrphaned(): void {
		// polls 1.4 -> introduced contraints
		// Version0104Date20200205104800
		// check for orphaned entries in all tables referencing
		// the main polls table
		// TODO: Move to command after polls5.x
		foreach (TableSchema::FK_CHILD_TABLES as $tableName) {
			$child = "$this->dbPrefix$tableName";
			$query = "DELETE
                FROM $child
                WHERE NOT EXISTS (
                    SELECT NULL
                    FROM {$this->dbPrefix}polls_polls polls
                    WHERE polls.id = {$child}.poll_id
                )";
			$this->connection->executeStatement($query);
		}
	}

	public function deleteDuplicates(): array {
		$messages = [];

		if ($this->schema->hasTable(Poll::TABLE)) {
			$this->removeOrphaned();

			$count[LogMapper::TABLE] = $this->logMapper->removeDuplicates();
			$count[OptionMapper::TABLE] = $this->optionMapper->removeDuplicates();
			$count[PreferencesMapper::TABLE] = $this->preferencesMapper->removeDuplicates();
			$count[ShareMapper::TABLE] = $this->shareMapper->removeDuplicates();
			$count[SubscriptionMapper::TABLE] = $this->subscriptionMapper->removeDuplicates();
			$count[VoteMapper::TABLE] = $this->voteMapper->removeDuplicates();
			$count[WatchMapper::TABLE] = $this->watchMapper->deleteOldEntries(time());

			foreach (TableSchema::UNIQUE_INDICES as $tableName => $value) {
				if ($count[$tableName]) {
					$messages[] = ' Removed ' . $count[$tableName] . ' duplicate records from ' . $tableName;
				}
			}
		}

		return $messages;
	}

	/**
	 * Tidy migrations table and remove obsolete migration entries.
	 */
	public function removeObsoleteMigrations(): array {
		$messages = [];
		$query = $this->connection->getQueryBuilder();
		$messages[] = 'tidy migration entries';
		foreach (TableSchema::GONE_MIGRATIONS as $version) {
			$query->delete('migrations')
				->where('app = :appName')
				->andWhere('version = :version')
				->setParameter('appName', 'polls')
				->setParameter('version', $version)
				->executeStatement();
		}
		return $messages;
	}
	public function fixVotes() {
		if ($this->schema->hasTable(OptionMapper::TABLE)) {
			$table = $this->schema->getTable(OptionMapper::TABLE);
			if ($table->hasColumn('duration')) {
				$foundOptions = $this->optionMapper->findOptionsWithDuration();
				foreach ($foundOptions as $option) {
					$this->voteMapper->fixVoteOptionText(
						$option->getPollId(),
						$option->getId(),
						$option->getPollOptionTextStart(),
						$option->getPollOptionText(),
					);
				}
			}
		}
	}
}
