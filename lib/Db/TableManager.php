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
use OCP\IConfig;
use OCP\IDBConnection;

class TableManager {
	private Schema $schema;
	private string $dbPrefix;

	public function __construct(
		private IConfig $config,
		private IDBConnection $connection,
		private LogMapper $logMapper,
		private OptionMapper $optionMapper,
		private PreferencesMapper $preferencesMapper,
		private ShareMapper $shareMapper,
		private SubscriptionMapper $subscriptionMapper,
		private VoteMapper $voteMapper,
		private WatchMapper $watchMapper
	) {
		$this->schema = $this->connection->createSchema();
		$this->dbPrefix = $this->config->getSystemValue('dbtableprefix', 'oc_');
	}

	/**
	 * execute the migration
	 */
	public function migrate(): void {
		$this->connection->migrateToSchema($this->schema);
	}

	public function refreshSchema(): void {
		$this->schema = $this->connection->createSchema();
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return non-empty-list<string>
	 */
	public function resetWatch(): array {
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

	/**
	 * @return string[]
	 *
	 * @psalm-return non-empty-list<string>
	 */
	public function createTables(): array {
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
		$messages = [];

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
		$count = [];

		if ($this->schema->hasTable($this->dbPrefix . Poll::TABLE)) {
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
	public function fixVotes(): void {
		if ($this->schema->hasTable($this->dbPrefix . OptionMapper::TABLE)) {
			$table = $this->schema->getTable($this->dbPrefix . OptionMapper::TABLE);
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

	public function migrateOptionsToHash(): array {
		$messages = [];

		if ($this->schema->hasTable($this->dbPrefix . OptionMapper::TABLE)) {
			$table = $this->schema->getTable($this->dbPrefix . OptionMapper::TABLE);
			$count = 0;
			if ($table->hasColumn('poll_option_hash')) {
				foreach ($this->optionMapper->getAll() as $option) {
					$option->setPollOptionHash(hash('md5', $option->getPollId() . $option->getPollOptionText() . $option->getTimestamp()));

					$this->optionMapper->update($option);
					$count++;
				}
			}
			$messages[] = 'Updated ' . $count . ' option hashes';
		}
		
		
		if ($this->schema->hasTable($this->dbPrefix . VoteMapper::TABLE)) {
			$table = $this->schema->getTable($this->dbPrefix . VoteMapper::TABLE);
			$count = 0;
			if ($table->hasColumn('vote_option_hash')) {
				foreach ($this->voteMapper->getAll() as $vote) {
					$vote->setVoteOptionHash(hash('md5', $vote->getPollId() . $vote->getUserId() . $vote->getVoteOptionText()));
					$this->voteMapper->update($vote);
					$count++;
				}
			}
			$messages[] = 'Updated ' . $count . ' vote hashes';
		}
		return $messages;
	}
}
