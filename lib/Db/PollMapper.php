<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Search\ISearchQuery;

/**
 * @template-extends QBMapper<Poll>
 */
class PollMapper extends QBMapper {
	public const TABLE = Poll::TABLE;

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, Poll::TABLE, Poll::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 * @return Poll
	 */
	public function find(int $id): Poll {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Poll[]
	 */
	public function findAutoReminderPolls(): array {
		$autoReminderSearchString = '%"autoReminder":true%';
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->like(
			self::TABLE . '.misc_settings',
			$qb->createNamedParameter($autoReminderSearchString, IQueryBuilder::PARAM_STR)
		));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Poll[]
	 */
	public function findForMe(string $userId): array {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.deleted', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->orWhere($qb->expr()->eq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Poll[]
	 */
	public function listByOwner(string $userId): array {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Poll[]
	 */
	public function search(ISearchQuery $query): array {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.deleted', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->orX(
				...array_map(function (string $token) use ($qb) {
					return $qb->expr()->orX(
						$qb->expr()->iLike(
							self::TABLE . '.title',
							$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($token) . '%', IQueryBuilder::PARAM_STR),
							IQueryBuilder::PARAM_STR
						),
						$qb->expr()->iLike(
							self::TABLE . '.description',
							$qb->createNamedParameter('%' . $this->db->escapeLikeParameter($token) . '%', IQueryBuilder::PARAM_STR),
							IQueryBuilder::PARAM_STR
						)
					);
				}, explode(' ', $query->getTerm()))
			));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Poll[]
	 */
	public function findForAdmin(string $userId): array {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->neq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

		return $this->findEntities($qb);
	}

	/**
	 * Archive polls per timestamp
	 */
	public function archiveExpiredPolls(int $offset): void {
		$archiveDate = time();
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('deleted', $qb->createNamedParameter($archiveDate))
			->where($qb->expr()->lt('expire', $qb->createNamedParameter($offset)))
			->andWhere($qb->expr()->gt('expire', $qb->createNamedParameter(0)));
		$qb->executeStatement();
	}

	/**
	 * Archive polls per timestamp
	 */
	public function setLastInteraction(int $pollId): void {
		$timestamp = time();
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('last_interaction', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));
		$qb->executeStatement();
	}

	/**
	 * Delete polls of named owner
	 */
	public function deleteByUserId(string $userId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where('owner = :userId')
			->setParameter('userId', $userId);
		$qb->executeStatement();
	}

	/**
	 * Build the enhanced query with joined tables
	 */
	protected function buildQuery(): IQueryBuilder {
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			// TODO: check if this is necessary, in case of empty table to avoid possibly nulled columns
			// ->groupBy(self::TABLE . '.id')
			->from($this->getTableName(), self::TABLE);
		$this->joinOptionsForMaxDate($qb, self::TABLE);
		$qb->groupBy(self::TABLE . '.id');
		return $qb;
	}

	/**
	 * Joins options to evaluate min and max option date for date polls
	 * if text poll or no options are set, 
	 * the min value is the current time,
	 * the max value is null
	 */
	protected function joinOptionsForMaxDate(IQueryBuilder &$qb, string $fromAlias): void {
		$joinAlias = 'options';
		$saveMin = (string) time();

		// force value into a MIN function to avoid grouping errors
		// $qb->selectAlias($qb->func()->max($joinAlias . '.timestamp'), 'max_date');
		$qb->addSelect($qb->createFunction('coalesce(MAX(' . $joinAlias . '.timestamp), 0) AS max_date'))
			->addSelect($qb->createFunction('coalesce(MIN(' . $joinAlias . '.timestamp), ' . $saveMin . ') AS min_date'));

		$qb->leftJoin(
			$fromAlias,
			Option::TABLE,
			$joinAlias,
			$qb->expr()->eq($fromAlias . '.id', $joinAlias . '.poll_id'),
		);
	}

}
