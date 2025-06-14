<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Db;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Exception;
use OCA\Polls\AppConstants;
use OCA\Polls\Migration\TableSchema;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PDO;
use Psr\Log\LoggerInterface;

class TableManager {

	private string $dbPrefix;

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private IConfig $config,
		private IDBConnection $connection,
		private LoggerInterface $logger,
		private OptionMapper $optionMapper,
		private VoteMapper $voteMapper,
		private Schema $schema,
		private WatchMapper $watchMapper,
	) {
		$this->setUp();
	}

	/**
	 * setUp
	 */
	private function setUp(): void {
		$this->dbPrefix = $this->config->getSystemValue('dbtableprefix', 'oc_');
	}

	public function setSchema(Schema &$schema): void {
		$this->schema = $schema;
	}

	public function setConnection(IDBConnection &$connection): void {
		$this->connection = $connection;
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return non-empty-list<string>
	 */
	public function purgeTables(): array {
		$messages = [];

		// drop all child tables
		$droppedTables = [];

		foreach (TableSchema::FK_CHILD_TABLES as $tableName) {
			if ($this->connection->tableExists($tableName)) {
				$this->connection->dropTable($tableName);
				$droppedTables[] = $this->dbPrefix . $tableName;
				$messages[] = 'Dropped ' . $this->dbPrefix . $tableName;
			}
		}

		foreach (TableSchema::FK_OTHER_TABLES as $tableName) {
			if ($this->connection->tableExists($tableName)) {
				$this->connection->dropTable($tableName);
				$droppedTables[] = $this->dbPrefix . $tableName;
				$messages[] = 'Dropped ' . $this->dbPrefix . $tableName;
			}
		}

		// drop parent table
		if ($this->connection->tableExists(TableSchema::FK_PARENT_TABLE)) {
			$this->connection->dropTable(TableSchema::FK_PARENT_TABLE);
			$droppedTables[] = $this->dbPrefix . TableSchema::FK_PARENT_TABLE;
			$messages[] = 'Dropped ' . $this->dbPrefix . TableSchema::FK_PARENT_TABLE;
		}
		if (!$droppedTables) {
			$this->logger->info('Dropped tables', $droppedTables);
		}

		// delete all migration records
		$query = $this->connection->getQueryBuilder();
		$query->delete('migrations')
			->where('app = :appName')
			->setParameter('appName', AppConstants::APP_ID)
			->executeStatement();

		$this->logger->info('Removed all migration records from {dbPrefix}migrations', ['dbPrefix' => $this->dbPrefix]);
		$messages[] = 'Removed all migration records from ' . $this->dbPrefix . 'migrations';

		// delete all app configs
		$query->delete('appconfig')
			->where('appid = :appid')
			->setParameter('appid', AppConstants::APP_ID)
			->executeStatement();

		$this->logger->info('Removed all app config records from {dbPrefix}appconfig', ['dbPrefix' => $this->dbPrefix]);
		$messages[] = 'Removed all app config records from ' . $this->dbPrefix . 'appconfig';
		$messages[] = 'Done.';
		$messages[] = '';
		$messages[] = 'Please call \'occ app:remove polls\' now!';

		return $messages;
	}

	/**
	 * @return string[]
	 */
	public function removeWatch(): array {
		$messages = [];
		$tableName = $this->dbPrefix . Watch::TABLE;

		if ($this->connection->tableExists(Watch::TABLE)) {
			$this->connection->dropTable(Watch::TABLE);
			$messages[] = 'Dropped ' . $tableName;
		}
		return $messages;
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return non-empty-list<string>
	 */
	public function createTable(string $tableName, array $columns): array {
		$messages = [];

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

	/**
	 * Remove obsolete tables if they still exist
	 */
	public function removeObsoleteTables(): array {
		$dropped = false;
		$messages = [];

		foreach (TableSchema::GONE_TABLES as $tableName) {
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

	/**
	 * @return string[]
	 *
	 * @psalm-return list<string>
	 */
	public function deleteAllDuplicates(?IOutput $output = null): array {
		$messages = [];
		foreach (TableSchema::UNIQUE_INDICES as $tableName => $index) {
			$count = $this->deleteDuplicates($tableName, $index['columns']);

			if ($count) {
				$messages[] = 'Removed ' . $count . ' duplicate records from ' . $this->dbPrefix . $tableName;
				$this->logger->info(end($messages));
			}

			if ($output && $count) {
				$output->info(end($messages));
			}
		}
		return $messages;

	}

	private function deleteDuplicates(string $table, array $columns):int {
		$this->watchMapper->deleteOldEntries(time());

		$qb = $this->connection->getQueryBuilder();

		if ($this->schema->hasTable($this->dbPrefix . $table)) {
			// identify duplicates
			$selection = $qb->selectDistinct('t1.id')
				->from($table, 't1')
				->innerJoin('t1', $table, 't2', $qb->expr()->lt('t1.id', 't2.id'));

			$i = 0;

			foreach ($columns as $column) {
				if ($i > 0) {
					$selection->andWhere($qb->expr()->eq('t1.' . $column, 't2.' . $column));
				} else {
					$selection->where($qb->expr()->eq('t1.' . $column, 't2.' . $column));
				}
				$i++;
			}

			$duplicates = $qb->executeQuery()->fetchAll(PDO::FETCH_COLUMN);

			$this->connection->getQueryBuilder()
				->delete($table)
				->where('id in (:ids)')
				->setParameter('ids', $duplicates, IQueryBuilder::PARAM_INT_ARRAY)
				->executeStatement();
			return count($duplicates);
		}
		return 0;
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
				->setParameter('appName', AppConstants::APP_ID)
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

	public function resetLastInteraction(?int $timestamp = null): array {
		$messages = [];
		$timestamp = $timestamp ?? time();
		$query = $this->connection->getQueryBuilder();

		$query->update(Poll::TABLE)
			->set('last_interaction', $query->createNamedParameter($timestamp))
			->where($query->expr()->eq('last_interaction', $query->createNamedParameter(0)));
		$count = $query->executeStatement();

		if ($count > 0) {
			$this->logger->info('Updated {number} polls in {db} and set last_interaction to current timestamp {timestamp}', ['number' => $count, 'db' => $this->dbPrefix . PollMapper::TABLE, 'timestamp' => $timestamp]);
			$messages[] = 'Updated ' . $count . ' polls';
		} else {
			$this->logger->info('No polls needed to get updated with last interaction info');
			$messages[] = 'No polls needed to get updated with last interaction info';
		}

		return $messages;
	}

	public function migrateOptionsToHash(): array {
		$messages = [];

		if ($this->schema->hasTable($this->dbPrefix . OptionMapper::TABLE)) {
			$table = $this->schema->getTable($this->dbPrefix . OptionMapper::TABLE);
			$count = 0;
			if ($table->hasColumn('poll_option_hash')) {
				foreach ($this->optionMapper->getAll() as $option) {
					$option->syncOption();
					// $option->setPollOptionHash(hash('md5', $option->getPollId() . $option->getPollOptionText() . $option->getTimestamp()));

					$this->optionMapper->update($option);
					$count++;
				}

				$this->logger->info('Updated {number} hashes in {db}', ['number' => $count,'db' => $this->dbPrefix . OptionMapper::TABLE]);
				$messages[] = 'Updated ' . $count . ' option hashes';

			} else {
				$this->logger->error('{db} is missing column \'poll_option_hash\' - aborted recalculating hashes', [ 'db' => $this->dbPrefix . OptionMapper::TABLE]);
			}
		} else {
			$this->logger->error('{db} is missing - aborted recalculating hashes', [ 'db' => $this->dbPrefix . OptionMapper::TABLE]);
		}

		if ($this->schema->hasTable($this->dbPrefix . VoteMapper::TABLE)) {
			$table = $this->schema->getTable($this->dbPrefix . VoteMapper::TABLE);
			$count = 0;
			if ($table->hasColumn('vote_option_hash')) {
				foreach ($this->voteMapper->getAll() as $vote) {
					try {
						$vote->setVoteOptionHash(hash('md5', $vote->getPollId() . $vote->getUserId() . $vote->getVoteOptionText()));
						$this->voteMapper->update($vote);
						$count++;
					} catch (Exception $e) {
						$messages[] = 'Skip hash update - Error updating option hash for voteId ' . $vote->getId();
						$this->logger->error('Error updating option hash for voteId {id}', ['id' => $vote->getId(), 'message' => $e->getMessage()]);
					}
				}

				$this->logger->info('Updated {number} hashes in {db}', ['number' => $count, 'db' => $this->dbPrefix . VoteMapper::TABLE]);
				$messages[] = 'Updated ' . $count . ' vote hashes';

			} else {
				$this->logger->error('{db} is missing column \'poll_option_hash\' - aborted recalculating hashes', ['db' => $this->dbPrefix . VoteMapper::TABLE]);
			}
		} else {
			$this->logger->error('{db} is missing- aborted recalculating hashes', ['db' => $this->dbPrefix . VoteMapper::TABLE]);
		}
		return $messages;
	}
}
