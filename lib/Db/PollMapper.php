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
use OCP\DB\QueryBuilder\IParameter;
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
	public function __construct(
		IDBConnection $db,
		private UserMapper $userMapper,
	) {
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
		$currentUserId = $this->userMapper->getCurrentUser()->getId();
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->groupBy(self::TABLE . '.id');

		$paramUser = $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR);
		$paramAnswerYes = $qb->createNamedParameter(Vote::VOTE_YES, IQueryBuilder::PARAM_STR);

		$qb->selectAlias($qb->createFunction('(' . $this->subQueryVotesCount(self::TABLE, $paramUser)->getSQL() . ')'), 'current_user_count_votes');
		$qb->selectAlias($qb->createFunction('(' . $this->subQueryVotesCount(self::TABLE, $paramUser, $paramAnswerYes)->getSQL() . ')'), 'current_user_count_votes_yes');
		$qb->selectAlias($qb->createFunction('(' . $this->subQueryOrphanedVotesCount(self::TABLE, $paramUser)->getSQL() . ')'), 'current_user_count_orphaned_votes');

		$this->joinOptionsForMaxDate($qb, self::TABLE);
		$this->joinUserRole($qb, self::TABLE, $currentUserId);

		return $qb;
	}

	/**
	 * Joins shares to evaluate user role
	 */
	protected function joinUserRole(IQueryBuilder &$qb, string $fromAlias, string $currentUserId): void {
		$joinAlias = 'shares';
		$qb->addSelect($qb->createFunction('coalesce(' . $joinAlias . '.type, ' . $qb->expr()->literal('') . ') AS user_role'))
			->addGroupBy($joinAlias . '.type');
		
		$qb->selectAlias($joinAlias . '.locked', 'is_current_user_locked')
			->addGroupBy($joinAlias . '.locked');

		$qb->leftJoin(
			$fromAlias,
			Share::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($fromAlias . '.id', $joinAlias . '.poll_id'),
				$qb->expr()->eq($joinAlias . '.user_id', $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
			)
		);
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

		$qb->addSelect($qb->createFunction('coalesce(MAX(' . $joinAlias . '.timestamp), 0) AS max_date'))
			->addSelect($qb->createFunction('coalesce(MIN(' . $joinAlias . '.timestamp), ' . $saveMin . ') AS min_date'));

		$qb->leftJoin(
			$fromAlias,
			Option::TABLE,
			$joinAlias,
			$qb->expr()->eq($fromAlias . '.id', $joinAlias . '.poll_id'),
		);
	}



	/**
	 * Subquery for votes count
	 */
	protected function subQueryVotesCount(string $fromAlias, IParameter $currentUserId, ?IParameter $answerFilter = null): IQueryBuilder {
		$subAlias = 'user_vote_sub';

		$subQuery = $this->db->getQueryBuilder();
		$subQuery->select($subQuery->func()->count($subAlias . '.vote_answer'))
			->from(Vote::TABLE, $subAlias)
			->where($subQuery->expr()->eq($subAlias . '.poll_id', $fromAlias . '.id'))
			->andWhere($subQuery->expr()->eq($subAlias . '.user_id', $currentUserId));

		// filter by answer
		if ($answerFilter) {
			$subQuery->andWhere($subQuery->expr()->eq($subAlias . '.vote_answer', $answerFilter));
		}

		return $subQuery;
	}

	/**
	 * Subquery for count of orphaned votes
	 */
	protected function subQueryOrphanedVotesCount(string $fromAlias, IParameter $currentUserId): IQueryBuilder {
		$subAlias = 'user_vote_sub';
		$subJoinAlias = 'vote_options_join';

		// use subQueryVotesCount as base query
		$subQuery = $this->subQueryVotesCount($fromAlias, $currentUserId);

		// superseed select, group result by voteId and add an additional condition
		$subQuery->select($subQuery->func()->count($subAlias . '.vote_answer'))
			->andWhere($subQuery->expr()->isNull($subJoinAlias . '.id'));

		// join options to restrict query to votes with actually undeleted options
		$subQuery->leftJoin(
			$subAlias,
			Option::TABLE,
			$subJoinAlias,
			$subQuery->expr()->andX(
				$subQuery->expr()->eq($subJoinAlias . '.poll_id', $subAlias . '.poll_id'),
				$subQuery->expr()->eq($subJoinAlias . '.poll_option_text', $subAlias . '.vote_option_text'),
				$subQuery->expr()->eq($subJoinAlias . '.deleted', $subQuery->expr()->literal(0, IQueryBuilder::PARAM_INT)),
			)
		);
		return $subQuery;
	}

}
