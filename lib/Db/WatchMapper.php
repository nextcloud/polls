<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use OCA\Polls\AppConstants;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

use function OCP\Log\logger;

/**
 * @template-extends QBMapper<Watch>
 */
class WatchMapper extends QBMapper {
	public const TABLE = Watch::TABLE;

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		IDBConnection $db,
		protected UserSession $userSession,
	) {
		parent::__construct($db, Watch::TABLE, Watch::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Watch[]
	 */
	public function findUpdatesForPollId(int $pollId, int $offset, bool $skipShares): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->gt('updated', $qb->createNamedParameter($offset)))
			->andWhere(
				$qb->expr()->neq('session_id', $qb->createNamedParameter($this->userSession->getClientIdHashed()))
			)
			->andWhere($qb->expr()->orX(
				$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId)),
				$qb->expr()->eq('table', $qb->createNamedParameter(Watch::OBJECT_POLLS))
			));

		if ($skipShares) {
			$qb->andWhere(
				$qb->expr()->neq('table', $qb->createNamedParameter(Watch::OBJECT_SHARES))
			);
		}

		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Watch
	 */
	public function findForPollIdAndTable(int $pollId, string $table): Watch {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId))
			)
			->andWhere(
				$qb->expr()->eq('table', $qb->createNamedParameter($table))
			)
			->andWhere(
				$qb->expr()->eq('session_id', $qb->createNamedParameter($this->userSession->getClientIdHashed()))
			);

		return $this->findEntity($qb);
	}
}
