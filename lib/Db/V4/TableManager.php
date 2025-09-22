<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db\V4;

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
use OCA\Polls\Exceptions\PreconditionException;
use OCA\Polls\Helper\Hash;
use OCA\Polls\Migration\V4\TableSchema;
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
		protected LoggerInterface $logger,
		private OptionMapper $optionMapper,
		private VoteMapper $voteMapper,
	) {
		parent::__construct($config, $connection, $logger);
	}

	/**
	 * Purge all tables and all data
	 *
	 * @return string[] Messages as array
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
	 * Remove the watch table if it exists
	 * Used as shorthand to reset the primary key autoincrement value
	 *
	 * @return string[] Messages as array
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
	 * Create or update a table defined in TableSchema::TABLES
	 *
	 * @return string[] Messages as array
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
	 * Create all tables defined in TableSchema::TABLES
	 *
	 * @return string[] Messages as array
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
	 *
	 * @return string[] Messages as array
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

	/**
	 * Remove obsolete columns if they still exist
	 *
	 * @return string[] Messages as array
	 */
	public function removeObsoleteColumns(): array {
		$messages = [];
		$dropped = false;

		foreach (TableSchema::GONE_COLUMNS as $tableName => $columns) {
			$prefixedTableName = $this->dbPrefix . $tableName;
			if (!$this->schema->hasTable($prefixedTableName)) {
				continue;
			}

			$table = $this->schema->getTable($prefixedTableName);

			foreach ($columns as $columnName) {
				if ($table->hasColumn($columnName)) {
					$dropped = true;
					$table->dropColumn($columnName);
					$messages[] = 'Dropped ' . $columnName . ' from ' . $prefixedTableName;
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
	 *
	 * @return string[] Messages as array
	 */
	public function removeOrphaned(): array {
		$orphanedCount = [];
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
				if (isset($orphanedCount[$tableName])) {
					$orphanedCount[$tableName] += $executed;
				} else {
					$orphanedCount[$tableName] = $executed;
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
		$orphanedCount[Share::TABLE] = $query->executeStatement();

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
		$orphanedCount[PollGroup::RELATION_TABLE] = $query->executeStatement();

		// finally delete all polls with id === null
		$query = $this->connection->getQueryBuilder();
		$query->delete(Poll::TABLE)
			->where($query->expr()->isNull('id'));
		$orphanedCount[Poll::TABLE] = $query->executeStatement();
		$messages = [];
		foreach ($orphanedCount as $tableName => $count) {
			if ($count > 0) {
				$this->logger->info(
					'Purged {count} orphaned record(s) from {tableName}',
					['count' => $count, 'tableName' => $tableName]
				);
			}
		}

		return $messages;
	}

	/**
	 * Delete all duplicate entries in all tables based on the unique indices defined in TableSchema::UNIQUE_INDICES
	 *
	 * @return string[] Messages as array
	 */
	public function deleteAllDuplicates(?IOutput $output = null): array {
		$messages = [];
		foreach (TableSchema::UNIQUE_INDICES as $tableName => $uniqueIndices) {
			foreach ($uniqueIndices as $definition) {

				// delete all duplicates based on the unique index definition
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
	 *
	 * @return string Message
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

	/**
	 * Delete duplicate entries in $table based on $columns
	 * Keep the entry with the lowest id
	 *
	 * @param string $table
	 * @param array $columns
	 * @return int number of deleted entries
	 */
	private function deleteDuplicates(string $table, array $columns):int {
		$this->needsSchema();
		if (!$this->schema->hasTable($this->dbPrefix . $table)) {
			return 0;
		}

		$qb = $this->connection->getQueryBuilder();

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

	/**
	 * Tidy migrations table and remove obsolete migration entries.
	 *
	 * @return string[] Messages as array
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

	/**
	 * Fix all votes with option text not matching the option text in the options table
	 * Precondition: The options table has to have a duration column
	 * This method is used to fix votes which were cast while the option text was changing
	 * because of a duration change.
	 *
	 * This method is used by the occ command `occ polls:db:rebuild` and while updating
	 */
	public function fixVotes(): void {
		if (!$this->schema->hasTable($this->dbPrefix . OptionMapper::TABLE)) {
			return;
		}

		$table = $this->schema->getTable($this->dbPrefix . OptionMapper::TABLE);

		if (!$table->hasColumn('duration')) {
			return;
		}

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

	/**
	 * Fix all shares with nullish group_id or poll_id
	 * Precondition have to be checked before
	 *
	 * @return string[] Messages as array
	 */
	public function fixNullishShares(): array {
		$messages = [];

		try {
			$tableName = Share::TABLE;
			$affectedColumns = ['group_id', 'poll_id'];
			$this->checkPrecondition($tableName, $affectedColumns);

			// set all nullish group_id and poll_id to 0
			foreach ($affectedColumns as $affectedColumn) {
				$count = $this->migrateNullishColumnToZero($tableName, $affectedColumn);

				if ($count > 0) {
					$messages[] = 'Updated ' . $count . ' shares with nullish ' . $affectedColumn . ' to 0';
				}
			}

		} catch (PreconditionException $e) {
			$messages[] = $e->getMessage() . ' - aborted fix nullish shares';
			return $messages;
		}

		if (empty($messages)) {
			$messages[] = 'All shares are valid';
		}

		return $messages;
	}

	/**
	 * Fix all poll group relations with nullish group_id or poll_id
	 * Precondition have to be checked before
	 *
	 * @return string[] Messages as array
	 */
	public function fixNullishPollGroupRelations(): array {
		$messages = [];

		try {
			$tableName = PollGroup::RELATION_TABLE;
			$affectedColumns = ['group_id', 'poll_id'];
			$this->checkPrecondition($tableName, $affectedColumns);

			$countAll = 0;
			// set all nullish group_id and poll_id to 0
			foreach ($affectedColumns as $affectedColumn) {
				$updateCount = $this->migrateNullishColumnToZero($tableName, $affectedColumn);

				if ($updateCount > 0) {
					$countAll += $updateCount;
					$messages[] = 'Updated ' . $updateCount . ' pollgroup relations and set ' . $affectedColumn . ' to 0 for nullish values';
				}
			}

		} catch (PreconditionException $e) {
			$messages[] = $e->getMessage() . ' - aborted fix nullish poll group relations';
			return $messages;
		}

		if ($countAll === 0) {
			$messages[] = 'All poll group relations are valid';
		}

		return $messages;
	}

	/**
	 * Migrate all nullish values in $columnName of $tableName to 0
	 *
	 * @param string $tableName Unprefixed tablename
	 * @param string $columnName Column name to update
	 *
	 * @return int number of updated entries
	 */
	private function migrateNullishColumnToZero(string $tableName, string $columnName): int {
		$query = $this->connection->getQueryBuilder();
		$query->update($tableName)
			->set($columnName, $query->createNamedParameter(0, IQueryBuilder::PARAM_INT))
			->where($query->expr()->isNull($columnName));

		$count = $query->executeStatement();
		return $count;
	}

	/**
	 * Set last interaction to current timestamp for all polls
	 * where last interaction is 0
	 *
	 * @param int|null $timestamp
	 * @return string
	 */
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

	/**
	 * Update all option and vote hashes
	 * Ensures the preconditions are met
	 *
	 * @return string[] Messages as array
	 */
	public function updateHashes(): array {
		// Do not catch any exceptions but let any operation break to ensure hash updates can be performed
		// Otherwise data loss of votes can occur
		$this->checkPrecondition(OptionMapper::TABLE, ['poll_id', 'poll_option_text', 'poll_option_hash']);
		$this->checkPrecondition(VoteMapper::TABLE, ['poll_id', 'vote_option_text', 'vote_option_hash']);

		$messages = $this->updateOptionHashes();
		$messages = array_merge($messages, $this->updateVoteHashes());
		return $messages;
	}

	/**
	 * Update all vote hashes
	 * Precondition have to be checked before
	 *
	 * @return string[] Messages as array
	 */
	private function updateVoteHashes(): array {
		$messages = [];

		$tableName = VoteMapper::TABLE;
		$prefixedTableName = $this->dbPrefix . $tableName;

		$count = 0;
		$updated = 0;

		foreach ($this->voteMapper->getAll(includeNull: true) as $vote) {
			try {
				// if the hash of the vote differs from calculated hash update the vote hash
				if ($vote->getVoteOptionHash() !== Hash::getOptionHash($vote->getPollId(), $vote->getVoteOptionText())) {
					$vote->setVoteOptionHash(Hash::getOptionHash($vote->getPollId(), $vote->getVoteOptionText()));
					$vote = $this->voteMapper->update($vote);
					$updated++;
				}

				$count++;

			} catch (Exception $e) {
				$messages[] = 'Skip hash update - Error updating option hash for voteId ' . $vote->getId();
				$this->logger->error('Error updating option hash for voteId {id}', [
					'id' => $vote->getId(),
					'message' => $e->getMessage()
				]);
			}
		}

		if ($updated === 0) {
			$this->logger->info('Verified {count} vote hashes in {db}', [
				'count' => $count,
				'db' => $prefixedTableName
			]);
			$messages[] = 'No vote hashes to update';

		} else {
			$this->logger->info('Updated {updated} hashes of {count} votes in {db}', [
				'updated' => $updated,
				'count' => $count,
				'db' => $prefixedTableName
			]);
			$messages[] = 'Updated ' . $updated . ' vote hashes';

		}

		return $messages;
	}

	/**
	 * Update all option hashes
	 * Precondition have to be checked before
	 *
	 * @return string[] Messages as array
	 */
	private function updateOptionHashes(): array {
		$messages = [];

		$tableName = OptionMapper::TABLE;
		$prefixedTableName = $this->dbPrefix . $tableName;

		$count = 0;
		$updated = 0;

		foreach ($this->optionMapper->getAll(includeNull: true) as $option) {
			try {
				// if the option's hash differs from $actualHash update the option
				if ($option->getPollOptionHash() !== Hash::getOptionHash($option->getPollId(), $option->getPollOptionText())) {
					$option->setPollOptionHash(Hash::getOptionHash($option->getPollId(), $option->getPollOptionText()));
					$option = $this->optionMapper->update($option);
					$updated++;
				}

				$count++;

			} catch (Exception $e) {
				$messages[] = 'Skip hash update - Error updating option hash for optionId ' . $option->getId();
				$this->logger->error('Error updating option hash for optionId {id}', ['id' => $option->getId(), 'message' => $e->getMessage()]);
			}
		}

		if ($updated === 0) {
			$this->logger->info('Verified {count} option hashes in {db}', [
				'count' => $count,
				'db' => $prefixedTableName
			]);
			$messages[] = 'No option hashes to update';

		} else {
			$this->logger->info('Updated {updated} hashes of {count} options in {db}', [
				'updated' => $updated,
				'count' => $count,
				'db' => $prefixedTableName
			]);
			$messages[] = 'Updated ' . $updated . ' option hashes';

		}

		return $messages;
	}

	/**
	 * Migrate all share labels to display_name
	 *
	 * @return string[] Messages as array
	 *
	 */
	public function migrateShareLabels(): array {
		$messages = [];

		$tableName = Share::TABLE;
		$affectedColumn = 'label';

		try {
			$this->checkPrecondition($tableName, $affectedColumn);
		} catch (PreconditionException $e) {
			$messages[] = $e->getMessage() . ' - aborted migrating labels';
			return $messages;
		}

		$prefixedTableName = $this->dbPrefix . $tableName;
		$qb = $this->connection->getQueryBuilder();

		$qb->update($tableName)
			->set('display_name', $affectedColumn)
			->andWhere($qb->expr()->isNotNull($tableName . '.' . $affectedColumn))
			->andWhere($qb->expr()->eq($tableName . '.' . $affectedColumn, $qb->expr()->literal('')));
		$updated = $qb->executeStatement();

		if ($updated === 0) {
			$this->logger->info('Verified all share labels in {db}', [
				'db' => $prefixedTableName
			]);
			$messages[] = 'No share labels to update';

		} else {
			$this->logger->info('Updated {updated} share labels in {db}', [
				'updated' => $updated,
				'db' => $prefixedTableName
			]);
			$messages[] = 'Updated ' . $updated . ' labels';
		}

		return $messages;
	}

	/**
	 * Migrate all polls with access 'public' to access 'open'
	 *
	 * @return string[] Messages as array
	 *
	 */
	public function migratePublicToOpen(): array {
		$messages = [];

		$tableName = Poll::TABLE;
		$affectedColumn = 'access';
		$prefixedTableName = $this->dbPrefix . $tableName;

		try {
			$this->checkPrecondition($tableName, $affectedColumn);
		} catch (PreconditionException $e) {
			$messages[] = $e->getMessage() . ' - aborted migrating public to open';
			return $messages;
		}

		$qb = $this->connection->getQueryBuilder();

		$qb->update($tableName)
			->set('access', $qb->expr()->literal(Poll::ACCESS_OPEN))
			->where($qb->expr()->eq($tableName . '.' . $affectedColumn, $qb->expr()->literal(Poll::ACCESS_PUBLIC)));
		$updated = $qb->executeStatement();

		if ($updated === 0) {
			$this->logger->info('Verified poll access to be \'open\' instead of \'public\' in {db}', [
				'db' => $prefixedTableName
			]);
			$messages[] = 'No poll access to update';

		} else {
			$this->logger->info('Updated {updated} access in {db}', [
				'updated' => $updated,
				'db' => $prefixedTableName
			]);
			$messages[] = 'Updated ' . $updated . ' poll accesses';

		}

		return $messages;
	}
}
