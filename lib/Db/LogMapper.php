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
			->where($qb->expr()->isNotNull('poll_id'))
			->andWhere($qb->expr()->eq('processed', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		return $this->findEntities($qb);
	}

	public function deleteByUserId(string $userId): void {
		$delete = $this->db->getQueryBuilder();
		$delete->delete($this->getTableName())
			->where('user_id = :userId')
			->setParameter('userId', $userId);
		$delete->setMaxResults(999);

		$delete->executeStatement();
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
