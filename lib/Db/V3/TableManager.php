<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db\V2;

use Doctrine\DBAL\Types\Type;
use Exception;
use OCA\Polls\AppConstants;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollGroup;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\Watch;
use OCA\Polls\Helper\Hash;
use OCA\Polls\Migration\V3\TableSchema;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PDO;
use Psr\Log\LoggerInterface;

class TableManager extends DbManager {

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		protected IConfig $config,
		protected IDBConnection $connection,
		private LoggerInterface $logger,
		private OptionMapper $optionMapper,
		private VoteMapper $voteMapper,
	) {
		parent::__construct($config, $connection);
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return non-empty-list<string>
	 */
	public function purgeTables(): array {
		$messages = [];
		$droppedTables = [];

		// First drop all tables that have foreign key constraints
		foreach (TableSchema::FK_INDICES as $parent => $child) {
			// drop all child tables referencing the parent table
			foreach (array_keys($child) as $table) {
				if ($this->connection->tableExists($table)) {
					$this->connection->dropTable($table);
					$droppedTables[] = $this->dbPrefix . $table;
					$messages[] = 'Dropped ' . $this->dbPrefix . $table;
				}
			}
			// drop the parent table
			if ($this->connection->tableExists($parent)) {
				$this->connection->dropTable($parent);
				$droppedTables[] = $this->dbPrefix . $parent;
				$messages[] = 'Dropped ' . $this->dbPrefix . $parent;
			}
		}

		// Then if there are any tables left, drop them
		foreach (array_keys(TableSchema::TABLES) as $tableName) {
			if ($this->connection->tableExists($tableName)) {
				$this->connection->dropTable($tableName);
				$droppedTables[] = $this->dbPrefix . $tableName;
				$messages[] = 'Dropped ' . $this->dbPrefix . $tableName;
			}
		}

		if ($droppedTables) {
			$this->logger->info('Dropped tables', $droppedTables);
		}

		// delete all migration records
		// ATTENTION: This is more or less an illegal access
		// to the migrations table which belong to the core
		$query = $this->connection->getQueryBuilder();
		$query->delete('migrations')
			->where('app = :appName')
			->setParameter('appName', AppConstants::APP_ID)
			->executeStatement();

		$this->logger->info('Removed all migration records from {dbPrefix}migrations', ['dbPrefix' => $this->dbPrefix]);
		$messages[] = 'Removed all migration records from ' . $this->dbPrefix . 'migrations';

		// delete all app configs
		// ATTENTION: This is more or less an illegal access
		// to the migrations table which belong to the core
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
	public function createTable(string $tableName): array {
		$this->needsSchema();

		$messages = [];
		$columns = TableSchema::TABLES[$tableName];

		// Ensure the table name is prefixed correctly
		$tableName = $this->getTableName($tableName);

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
				$messages[] = "Added {$table->getName()}, {$columnName} ({$columnDefinition['type']})";
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
		$this->needsSchema();
		$messages = [];

		foreach (array_keys(TableSchema::TABLES) as $tableName) {
			$messages = array_merge($messages, $this->createTable($tableName));
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
	 * Because we allowed nullish poll_ids between version 8.0.0 and 8.1.0,
	 * we also delete all entries with a nullish poll_id.
	 *
	 * This method is used to clean up orphaned entries in the database and
	 * is used by the occ command `occ polls:db:rebuild and while updating
	 */
	public function removeOrphaned(): array {
		// collects all pollIds
		$subqueryPolls = $this->connection->getQueryBuilder();
		$subqueryPolls->selectDistinct('id')->from(Poll::TABLE);

		// collects all groupIds
		$subqueryGroups = $this->connection->getQueryBuilder();
		$subqueryGroups->selectDistinct('id')->from(PollGroup::TABLE);

		// delete all orphaned entries without a corresponding poll (poll_id is NULL or not in the polls table)
		foreach (TableSchema::FK_INDICES as $children) {
			foreach (array_keys($children) as $tableName) {
				$query = $this->connection->getQueryBuilder();
				$query->delete($tableName)
					->where(
						$query->expr()->orX(
							$query->expr()->notIn('poll_id', $query->createFunction($subqueryPolls->getSQL()), IQueryBuilder::PARAM_INT_ARRAY),
							$query->expr()->isNull('poll_id')
						)
					);
				$executed = $query->executeStatement();
				if (isset($orphaned[$tableName])) {
					$orphaned[$tableName] += $executed;
				} else {
					$orphaned[$tableName] = $executed;
				}
			}
		}

		// delete all orphaned shares without corresponding poll group and poll (group_id and poll_id are NULL or not in the polls or poll groups table)
		$query = $this->connection->getQueryBuilder();
		$query->delete(Share::TABLE)
			->where(
				$query->expr()->orX(
					$query->expr()->notIn('poll_id', $query->createFunction($subqueryPolls->getSQL()), IQueryBuilder::PARAM_INT_ARRAY),
					$query->expr()->isNull('poll_id')
				)
			);
		$query->andWhere(
			$query->expr()->orX(
				$query->expr()->notIn('group_id', $query->createFunction($subqueryGroups->getSQL()), IQueryBuilder::PARAM_INT_ARRAY),
				$query->expr()->isNull('group_id')
			)
		);
		$orphaned[Share::TABLE] = $query->executeStatement();

		// delete all orphaned entries from the poll-group-relation (group_id or poll_id are NULL or not in the polls or poll groups table)
		$query = $this->connection->getQueryBuilder();
		$query->delete(PollGroup::RELATION_TABLE)
			->where(
				$query->expr()->orX(
					$query->expr()->notIn('poll_id', $query->createFunction($subqueryPolls->getSQL()), IQueryBuilder::PARAM_INT_ARRAY),
					$query->expr()->isNull('poll_id')
				)
			);
		$query->orWhere(
			$query->expr()->orX(
				$query->expr()->notIn('group_id', $query->createFunction($subqueryGroups->getSQL()), IQueryBuilder::PARAM_INT_ARRAY),
				$query->expr()->isNull('group_id')
			)
		);
		$orphaned[PollGroup::RELATION_TABLE] = $query->executeStatement();


		// finally delete all polls with id === null
		$query = $this->connection->getQueryBuilder();
		$query->delete(Poll::TABLE)
			->where($query->expr()->isNull('id'));
		$orphaned[Poll::TABLE] = $query->executeStatement();
		$messages = [];
		foreach ($orphaned as $type => $count) {
			if ($count > 0) {
				$this->logger->info(
					'Purged ' . $count . ' orphaned record(s) from ' . $type,
					['count' => $count, 'type' => $type]
				);
			}
		}

		return $messages;
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return list<string>
	 */
	public function deleteAllDuplicates(?IOutput $output = null): array {
		$messages = [];
		foreach (TableSchema::UNIQUE_INDICES as $tableName => $uniqueIndices) {
			foreach ($uniqueIndices as $definition) {

				$count = $this->deleteDuplicates($tableName, $definition['columns']);

				if ($count) {
					$messages[] = 'Removed ' . $count . ' duplicate records from ' . $this->dbPrefix . $tableName;
					$this->logger->info(end($messages));
				}

				if ($output && $count) {
					$output->info(end($messages));
				}
			}
		}
		return $messages;
	}

	/**
	 * Delete entries per timestamp
	 */
	public function tidyWatchTable(int $offset): string {
		$query = $this->connection->getQueryBuilder();
		$query->delete(Watch::TABLE)
			->where(
				$query->expr()->lt('updated', $query->createNamedParameter($offset))
			);
		$count = $query->executeStatement();

		if ($count > 0) {
			$this->logger->info('Removed {number} old watch records', ['number' => $count, 'db' => $this->dbPrefix . Watch::TABLE]);
			return 'Removed ' . $count . ' old watch records';
		}

		$this->logger->info('Watch table is clean');
		return 'Watch table is clean';
	}

	private function deleteDuplicates(string $table, array $columns):int {
		$this->needsSchema();
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

	public function fixNullishShares(): array {
		$messages = [];
		$query = $this->connection->getQueryBuilder();
		$schema = $this->connection->createSchema();

		if (!$schema->hasTable($this->dbPrefix . Share::TABLE)) {
			$messages[] = 'Table ' . $this->dbPrefix . Share::TABLE . ' does not exist';
			return $messages;
		}

		$table = $schema->getTable($this->dbPrefix . Share::TABLE);

		if ($table->hasColumn('group_id')) {
			// replace all nullish group_ids with 0 in share table
			$query->update(Share::TABLE)
				->set('group_id', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT))
				->where($query->expr()->isNull('group_id'));
			$count = $query->executeStatement();

			if ($count > 0) {
				$messages[] = 'Updated ' . $count . ' shares with nullish group_id and set group_id to 0';
			}
		}

		// replace all nullish poll_id with 0 in share table
		$query = $this->connection->getQueryBuilder();
		$query->update(Share::TABLE)
			->set('poll_id', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT))
			->where($query->expr()->isNull('poll_id'));

		$count = $query->executeStatement();

		if ($count > 0) {
			$messages[] = 'Updated ' . $count . ' shares and set poll_id to 0 for nullish values';
		}

		if (empty($messages)) {
			return ['All shares are valid'];
		}

		return $messages;
	}

	public function fixNullishPollGroupRelations(): array {
		$messages = [];
		$query = $this->connection->getQueryBuilder();
		$schema = $this->connection->createSchema();

		if (!$schema->hasTable($this->dbPrefix . PollGroup::RELATION_TABLE)) {
			$messages[] = 'Table ' . $this->dbPrefix . PollGroup::RELATION_TABLE . ' does not exist';
			return $messages;
		}

		// replace all nullish group_ids with 0 in share table
		$query->update(PollGroup::RELATION_TABLE)
			->set('group_id', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT))
			->where($query->expr()->isNull('group_id'));

		$count = $query->executeStatement();

		if ($count > 0) {
			$messages[] = 'Updated ' . $count . ' pollgroup relations and set group_id to 0 for nullish values';
		}

		// replace all nullish poll_id with 0 in share table
		$query = $this->connection->getQueryBuilder();
		$query->update(PollGroup::RELATION_TABLE)
			->set('poll_id', $query->createNamedParameter(0, IQueryBuilder::PARAM_INT))
			->where($query->expr()->isNull('poll_id'));

		$count = $query->executeStatement();

		if ($count > 0) {
			$messages[] = 'Updated ' . $count . ' poll group relations and set poll_id to 0 for nullish values';
		}

		if (empty($messages)) {
			return ['All poll group relations are valid'];
		}

		return $messages;
	}

	public function setLastInteraction(?int $timestamp = null): string {
		$timestamp = $timestamp ?? time();
		$query = $this->connection->getQueryBuilder();

		$query->update(Poll::TABLE)
			->set('last_interaction', $query->createNamedParameter($timestamp))
			->where($query->expr()->eq('last_interaction', $query->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		$count = $query->executeStatement();

		if ($count > 0) {
			$this->logger->info('Updated {number} polls in {db} and set last_interaction to current timestamp {timestamp}', ['number' => $count, 'db' => $this->dbPrefix . PollMapper::TABLE, 'timestamp' => $timestamp]);
			return 'Updated last interaction in ' . $count . ' polls';
		}

		$this->logger->info('No polls needed to get updated with last interaction info');
		return 'Last interaction all set';

	}

	public function migrateOptionsToHash(): array {
		$this->needsSchema();
		$messages = [];

		if ($this->schema->hasTable($this->dbPrefix . OptionMapper::TABLE)) {
			$table = $this->schema->getTable($this->dbPrefix . OptionMapper::TABLE);
			$count = 0;
			if ($table->hasColumn('poll_option_hash')) {
				foreach ($this->optionMapper->getAll(includeNull: true) as $option) {
					try {
						$option->syncOption();
						$this->optionMapper->update($option);
						$count++;
					} catch (Exception $e) {
						$messages[] = 'Skip hash update - Error updating option hash for optionId ' . $option->getId();
						$this->logger->error('Error updating option hash for optionId {id}', ['id' => $option->getId(), 'message' => $e->getMessage()]);
					}
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
				foreach ($this->voteMapper->getAll(includeNull: true) as $vote) {
					try {
						$vote->setVoteOptionHash(Hash::getOptionHash($vote->getPollId(), $vote->getVoteOptionText()));
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
