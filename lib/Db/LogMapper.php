<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Log>
 */
class LogMapper extends QBMapper {
	public const TABLE = Log::TABLE;

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, Log::TABLE, Log::class);
	}

	/**
	 * @return Log[]
	 * @psalm-return array<array-key, Log>
	 */
	public function findUnprocessed(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->isNotNull('poll_id'));
		return $this->findEntities($qb);
	}

	public function deleteByUserId(string $userId): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where('user_id = :userId')
			->setParameter('userId', $userId);
		$query->executeStatement();
	}

	/**
	 * Delete entries per timestamp
	 */
	public function deleteOldEntries(int $offset): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where(
				$query->expr()->lt('created', $query->createNamedParameter($offset))
			);
		$query->executeStatement();
	}

	public function deleteOrphaned(): int {
		// collects all pollIds
		$subqueryPolls = $this->db->getQueryBuilder();
		$subqueryPolls->selectDistinct('id')->from(Poll::TABLE);

		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where(
				$query->expr()->orX(
					$query->expr()->notIn('poll_id', $query->createFunction($subqueryPolls->getSQL()), IQueryBuilder::PARAM_INT_ARRAY),
					$query->expr()->isNull('poll_id')
				)
			);
		return $query->executeStatement();
	}

	/**
	 * Delete processed entries
	 */
	public function deleteProcessedEntries(): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where($query->expr()->isNull('poll_id'));
		$query->executeStatement();
	}
}
