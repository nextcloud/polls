<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use Exception;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Share>
 */
class ShareMapper extends QBMapper {
	public const TABLE = Share::TABLE;

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		IDBConnection $db,
	) {
		parent::__construct($db, Share::TABLE, Share::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Share[]
	 * @psalm-return array<array-key, Share>
	 */
	public function findByPoll(int $pollId, array $groupIds = [], bool $getDeleted = false): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->groupBy(self::TABLE . '.id')
			->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));

		if (!empty($groupIds)) {
			$qb->orWhere(
				$qb->expr()->in(self::TABLE . '.group_id', $qb->createNamedParameter($groupIds, IQueryBuilder::PARAM_INT_ARRAY))
			);
		}
		if (!$getDeleted) {
			$qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		}

		$this->joinUserVoteCount($qb, self::TABLE);
		$this->joinAnon($qb, self::TABLE);

		return $this->findEntities($qb);
	}
	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Share[]
	 * @psalm-return array<array-key, Share>
	 */
	public function findByPollGroup(int $pollGroupId, bool $getDeleted = false): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->groupBy(self::TABLE . '.id')
			->where($qb->expr()->eq(self::TABLE . '.group_id', $qb->createNamedParameter($pollGroupId, IQueryBuilder::PARAM_INT)));

		if (!$getDeleted) {
			$qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		}

		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Share[]
	 * @psalm-return array<array-key, Share>
	 */
	public function findByPollNotInvited(int $pollId, bool $getDeleted = false): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('invitation_sent', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));

		if (!$getDeleted) {
			$qb->andWhere($qb->expr()->eq('deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		}

		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Share[]
	 * @psalm-return array<array-key, Share>
	 */
	public function findByPollUnreminded(int $pollId, bool $getDeleted = false): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('reminder_sent', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));

		if (!$getDeleted) {
			$qb->andWhere($qb->expr()->eq('deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		}

		return $this->findEntities($qb);
	}

	/**
	 * @throws ShareNotFoundException if not found
	 */
	public function findByPollAndUser(int $pollId, string $userId, bool $findDeleted = false): Share {
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->groupBy(self::TABLE . '.id')
			->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq(self::TABLE . '.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->isNotNull(self::TABLE . '.id'));

		if (!$findDeleted) {
			$qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		}
		$this->joinUserVoteCount($qb, self::TABLE);
		$this->joinAnon($qb, self::TABLE);

		try {
			return $this->findEntity($qb);
		} catch (Exception $e) {
			throw new ShareNotFoundException('Share not found by userId and pollId');
		}
	}

	/**
	 * @throws ShareNotFoundException
	 */
	public function findByToken(string $token, bool $getDeleted = false): Share {
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->groupBy(self::TABLE . '.id')
			->where($qb->expr()->eq(self::TABLE . '.token', $qb->createNamedParameter($token, IQueryBuilder::PARAM_STR)));

		if (!$getDeleted) {
			$qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		}

		$this->joinUserVoteCount($qb, self::TABLE);
		$this->joinAnon($qb, self::TABLE);

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			throw new ShareNotFoundException('Token ' . $token . ' does not exist');
		}
	}

	/**
	 * @return void
	 */
	public function deleteByIdAndType(string $id, string $type): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where('user_id = :id')
			->andWhere('type = :type')
			->setParameter('id', $id)
			->setParameter('type', $type);
		$query->executeStatement();
	}

	public function purgeDeletedShares(int $offset): int {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where(
				$query->expr()->gt('deleted', $query->expr()->literal(0, IQueryBuilder::PARAM_INT))
			)
			->andWhere(
				$query->expr()->lt('deleted', $query->createNamedParameter($offset))
			);
		return $query->executeStatement();
	}

	/**
	 * Joins votes count of the share user in the given poll
	 */
	protected function joinUserVoteCount(
		IQueryBuilder &$qb,
		string $fromAlias,
		string $joinAlias = 'votes',
	): void {

		$qb->addSelect($qb->func()->count($joinAlias . '.id', 'voted'));

		$qb->leftJoin(
			$fromAlias,
			Vote::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($joinAlias . '.poll_id', $fromAlias . '.poll_id'),
				$qb->expr()->eq($joinAlias . '.user_id', $fromAlias . '.user_id'),
			)
		);
	}

	/**
	 * Joins anonymous setting of poll
	 */
	protected function joinAnon(
		IQueryBuilder &$qb,
		string $fromAlias,
		string $joinAlias = 'anon',
	): void {

		$qb->selectAlias($joinAlias . '.anonymous', 'anonymizedVotes')
			->addGroupBy(
				$joinAlias . '.anonymous',
			);

		$qb->leftJoin(
			$fromAlias,
			Poll::TABLE,
			$joinAlias,
			$qb->expr()->eq($joinAlias . '.id', $fromAlias . '.poll_id'),
		);
	}
}
