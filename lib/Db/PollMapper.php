<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use OCA\Polls\Helper\SqlHelper;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\ICompositeExpression;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Search\ISearchQuery;

/**
 * @template-extends QBMapper<Poll>
 */
class PollMapper extends QBMapper {
	public const TABLE = Poll::TABLE;
	public const CONCAT_SEPARATOR = ',';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		IDBConnection $db,
		private UserSession $userSession,
	) {
		parent::__construct($db, Poll::TABLE, Poll::class);
	}

	/**
	 * Get active poll without any joins for backend operations
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 * @return Poll
	 */
	public function get(int $id): Poll {
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
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->like('misc_settings', $qb->createNamedParameter($autoReminderSearchString, IQueryBuilder::PARAM_STR)))
			->andwhere($qb->expr()->eq('deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));

		return $this->findEntities($qb);
	}

	/**
	 * Find the polls of the current user's poll list
	 *
	 * The optional filters only narrow down the candidates in SQL. They never
	 * drop a poll, which could match. Poll::getCategories() stays the authority
	 * and must still be evaluated by the caller.
	 *
	 * @param string|null $category one of Poll::CATEGORIES, ignored if $pollGroupId is set
	 * @param int|null $pollGroupId only polls of this poll group
	 * @param string|null $type only polls of this type
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Poll[]
	 */
	public function findForMe(
		string $userId,
		?string $category = null,
		?int $pollGroupId = null,
		?string $type = null,
	): array {
		$qb = $this->buildQuery(detailed: false);
		$this->applyPollListFilter($qb, $userId, $category, $pollGroupId, $type);
		return $this->findEntities($qb);
	}

	/**
	 * One sorted page of findForMe()
	 *
	 * Unlike findForMe() this skips the entity side category check, so it may
	 * only be used for the categories, whose SQL condition is exact. See
	 * PollService::hasExactCategoryCondition().
	 *
	 * @param string $sortColumn a numeric column of the polls table
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Poll[]
	 */
	public function findPageForMe(
		string $userId,
		string $category,
		?string $type,
		string $sortColumn,
		bool $descending,
		int $offset,
		int $limit,
	): array {
		$qb = $this->buildQuery(detailed: false);
		$this->applyPollListFilter($qb, $userId, $category, null, $type);

		$direction = $descending ? 'DESC' : 'ASC';
		$qb->orderBy(self::TABLE . '.' . $sortColumn, $direction)
			// tie breaker, so paging over equal sort values does not skip or repeat polls
			->addOrderBy(self::TABLE . '.id', $direction)
			->setFirstResult($offset)
			->setMaxResults($limit);

		return $this->findEntities($qb);
	}

	/**
	 * Number of polls matching findForMe() without loading them
	 *
	 * Counts the candidates of the SQL conditions, so this is only exact for the
	 * categories of PollService::hasExactCategoryCondition().
	 */
	public function countForMe(
		string $userId,
		?string $category = null,
		?int $pollGroupId = null,
		?string $type = null,
	): int {
		// no joins needed, all conditions are either on the polls table or EXISTS subqueries
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->func()->count(self::TABLE . '.id'))
			->from($this->getTableName(), self::TABLE);
		$this->applyPollListFilter($qb, $userId, $category, $pollGroupId, $type);

		$result = $qb->executeQuery();
		$count = (int)$result->fetchOne();
		$result->closeCursor();

		return $count;
	}

	/**
	 * Add the poll list conditions of findForMe() to a query on the polls table
	 */
	private function applyPollListFilter(
		IQueryBuilder $qb,
		string $userId,
		?string $category,
		?int $pollGroupId,
		?string $type,
	): void {
		$expr = $qb->expr();
		$userParam = $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR);

		$qb->where($expr->orX(
			$expr->eq(self::TABLE . '.deleted', $expr->literal(0, IQueryBuilder::PARAM_INT)),
			$expr->eq(self::TABLE . '.owner', $userParam),
		));

		if ($type !== null) {
			$qb->andWhere($expr->eq(self::TABLE . '.type', $qb->createNamedParameter($type, IQueryBuilder::PARAM_STR)));
		}

		if ($pollGroupId !== null) {
			// EXISTS instead of filtering the poll groups join, which would truncate the concatenated poll groups
			$subQuery = $this->db->getQueryBuilder();
			$subQuery->select($subQuery->expr()->literal(1))
				->from(PollGroup::RELATION_TABLE, 'group_filter')
				->where($subQuery->expr()->eq('group_filter.poll_id', self::TABLE . '.id'))
				->andWhere($subQuery->expr()->eq('group_filter.group_id', $qb->createNamedParameter($pollGroupId, IQueryBuilder::PARAM_INT)));
			$qb->andWhere((string)$qb->createFunction('EXISTS (' . $subQuery->getSQL() . ')'));
			return;
		}

		$condition = $category !== null
			? $this->getCategoryCondition($qb, $category, $userParam)
			: $this->anyCategoryCondition($qb, $userParam);

		if ($condition !== null) {
			$qb->andWhere($condition);
		}
	}

	/**
	 * SQL condition, which is a superset of the union of all categories of
	 * Poll::getCategories(), i.e. of every poll, which shows up in any poll list
	 *
	 * Without it an unfiltered poll list would scan every poll of the instance.
	 *
	 * @return ICompositeExpression|null null, if no condition applies
	 */
	private function anyCategoryCondition(IQueryBuilder $qb, IParameter $userParam): ?ICompositeExpression {
		// site admins have every poll they do not own in their admin category
		if ($this->userSession->getCurrentUser()->getIsAdmin()) {
			return null;
		}

		// every remaining category requires access to the poll, archived polls are
		// already limited to the current user's own polls by the base condition
		return $this->mayViewCondition($qb, $userParam);
	}

	/**
	 * SQL condition, which is a superset of Poll::getCategories() for the category
	 * Exact permission checks (group memberships, locked shares) are left to the entity
	 *
	 * @return ICompositeExpression|string|null null, if no condition applies
	 */
	private function getCategoryCondition(IQueryBuilder $qb, string $category, IParameter $userParam): ICompositeExpression|string|null {
		$expr = $qb->expr();
		$notDeleted = $expr->eq(self::TABLE . '.deleted', $expr->literal(0, IQueryBuilder::PARAM_INT));

		return match ($category) {
			Poll::CATEGORY_ALL => $expr->andX(
				$notDeleted,
				$this->mayViewCondition($qb, $userParam),
			),
			Poll::CATEGORY_MY => $expr->andX(
				$notDeleted,
				$expr->eq(self::TABLE . '.owner', $userParam),
			),
			// legacy access values are mapped by Poll::getAccess()
			Poll::CATEGORY_PRIVATE => $expr->andX(
				$notDeleted,
				$expr->in(self::TABLE . '.access', $qb->createNamedParameter(['private', 'hidden'], IQueryBuilder::PARAM_STR_ARRAY)),
				$this->mayViewCondition($qb, $userParam),
			),
			Poll::CATEGORY_OPEN => $expr->andX(
				$notDeleted,
				$expr->in(self::TABLE . '.access', $qb->createNamedParameter(['open', 'public'], IQueryBuilder::PARAM_STR_ARRAY)),
			),
			Poll::CATEGORY_CLOSED => $expr->andX(
				$notDeleted,
				$expr->gt(self::TABLE . '.expire', $expr->literal(0, IQueryBuilder::PARAM_INT)),
				$expr->lt(self::TABLE . '.expire', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT)),
				$this->mayViewCondition($qb, $userParam),
			),
			// combined with the base condition, these are the current user's archived polls
			Poll::CATEGORY_ARCHIVED => $expr->gt(self::TABLE . '.deleted', $expr->literal(0, IQueryBuilder::PARAM_INT)),
			Poll::CATEGORY_PARTICIPATED => $expr->andX(
				$notDeleted,
				$this->existsSubQuery($qb, Vote::TABLE, 'vote_filter', $userParam),
			),
			// relevant = involved || (view && not open). Both are covered by the
			// view superset without its open access branch
			Poll::CATEGORY_RELEVANT => $expr->andX(
				$notDeleted,
				$this->relevantCondition($qb, time() - Poll::RELEVANT_PERIOD),
				$this->mayViewCondition($qb, $userParam, includeOpenAccess: false),
			),
			// only site admins have the admin category
			Poll::CATEGORY_ADMIN => $this->userSession->getCurrentUser()->getIsAdmin()
				? $expr->neq(self::TABLE . '.owner', $userParam)
				: $expr->eq($expr->literal(1, IQueryBuilder::PARAM_INT), $expr->literal(0, IQueryBuilder::PARAM_INT)),
			default => null,
		};
	}

	/**
	 * Superset of Poll::getAllowAccessPoll() for logged in users
	 *
	 * Uses EXISTS subqueries instead of the joined share columns, because filtering
	 * on joined rows would truncate the concatenated columns (poll groups, group shares)
	 *
	 * @param bool $includeOpenAccess false limits the superset to involved users and session shares
	 */
	private function mayViewCondition(IQueryBuilder $qb, IParameter $userParam, bool $includeOpenAccess = true): ICompositeExpression {
		$expr = $qb->expr();
		$conditions = [
			$expr->eq(self::TABLE . '.owner', $userParam),
			// participant
			$this->existsSubQuery($qb, Vote::TABLE, 'vote_filter', $userParam),
			// personal share of any type (user, admin, email, contact, external)
			$this->existsSubQuery($qb, Share::TABLE, 'share_filter', $userParam),
		];
		if ($includeOpenAccess) {
			$conditions[] = $expr->in(self::TABLE . '.access', $qb->createNamedParameter(['open', 'public'], IQueryBuilder::PARAM_STR_ARRAY));
		}

		// any group share, the group membership is checked by the entity
		$groupShares = $this->db->getQueryBuilder();
		$groupShares->select($groupShares->expr()->literal(1))
			->from(Share::TABLE, 'group_share_filter')
			->where($groupShares->expr()->eq('group_share_filter.poll_id', self::TABLE . '.id'))
			->andWhere($groupShares->expr()->eq('group_share_filter.type', $qb->createNamedParameter(Share::TYPE_GROUP, IQueryBuilder::PARAM_STR)))
			->andWhere($groupShares->expr()->eq('group_share_filter.deleted', $groupShares->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		$conditions[] = (string)$qb->createFunction('EXISTS (' . $groupShares->getSQL() . ')');

		// share of a poll group, the poll belongs to
		$pollGroupShares = $this->db->getQueryBuilder();
		$pollGroupShares->select($pollGroupShares->expr()->literal(1))
			->from(PollGroup::RELATION_TABLE, 'poll_group_filter')
			->innerJoin('poll_group_filter', Share::TABLE, 'poll_group_share_filter', $pollGroupShares->expr()->eq('poll_group_share_filter.group_id', 'poll_group_filter.group_id'))
			->where($pollGroupShares->expr()->eq('poll_group_filter.poll_id', self::TABLE . '.id'))
			->andWhere($pollGroupShares->expr()->eq('poll_group_share_filter.user_id', $userParam))
			->andWhere($pollGroupShares->expr()->eq('poll_group_share_filter.deleted', $pollGroupShares->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		$conditions[] = (string)$qb->createFunction('EXISTS (' . $pollGroupShares->getSQL() . ')');

		// poll of the share the session was opened with
		$sessionShare = $this->userSession->getShare();
		if ($sessionShare->getId()) {
			$conditions[] = $expr->eq(self::TABLE . '.id', $qb->createNamedParameter($sessionShare->getPollId(), IQueryBuilder::PARAM_INT));
		}

		return $expr->orX(...$conditions);
	}

	/**
	 * EXISTS condition for a row of the current user in a table with poll_id and user_id
	 * Deleted shares are excluded, votes have no deleted state
	 */
	private function existsSubQuery(IQueryBuilder $qb, string $table, string $alias, IParameter $userParam): string {
		$subQuery = $this->db->getQueryBuilder();
		$subQuery->select($subQuery->expr()->literal(1))
			->from($table, $alias)
			->where($subQuery->expr()->eq($alias . '.poll_id', self::TABLE . '.id'))
			->andWhere($subQuery->expr()->eq($alias . '.user_id', $userParam));
		if ($table === Share::TABLE) {
			$subQuery->andWhere($subQuery->expr()->eq($alias . '.deleted', $subQuery->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		}
		return (string)$qb->createFunction('EXISTS (' . $subQuery->getSQL() . ')');
	}

	/**
	 * Equivalent to Poll::getRelevantThreshold() > $threshold
	 */
	private function relevantCondition(IQueryBuilder $qb, int $threshold): ICompositeExpression {
		$expr = $qb->expr();
		$thresholdParam = $qb->createNamedParameter($threshold, IQueryBuilder::PARAM_INT);

		$subQuery = $this->db->getQueryBuilder();
		$subQuery->select($subQuery->expr()->literal(1))
			->from(Option::TABLE, 'option_filter')
			->where($subQuery->expr()->eq('option_filter.poll_id', self::TABLE . '.id'))
			->andWhere($subQuery->expr()->eq('option_filter.deleted', $subQuery->expr()->literal(0, IQueryBuilder::PARAM_INT)))
			->andWhere($subQuery->expr()->gt('option_filter.timestamp', $thresholdParam));

		return $expr->orX(
			$expr->gt(self::TABLE . '.created', $thresholdParam),
			$expr->gt(self::TABLE . '.last_interaction', $thresholdParam),
			$expr->gt(self::TABLE . '.expire', $thresholdParam),
			(string)$qb->createFunction('EXISTS (' . $subQuery->getSQL() . ')'),
		);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Poll[]
	 */
	public function listByOwner(string $userId): array {
		$qb = $this->buildQuery(detailed: false);
		$qb->where($qb->expr()->eq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Poll[]
	 */
	public function search(ISearchQuery $query): array {
		$qb = $this->buildQuery(detailed: false);
		$qb->where($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)))
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
		$qb = $this->buildQuery(detailed: false);
		$qb->where($qb->expr()->neq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

		return $this->findEntities($qb);
	}

	/**
	 * Archive polls per timestamp
	 */
	public function archiveExpiredPolls(int $offset): int {
		$archiveDate = time();
		$qb = $this->db->getQueryBuilder();
		$qb->update($this->getTableName())
			->set('deleted', $qb->createNamedParameter($archiveDate))
			->where($qb->expr()->lt('expire', $qb->createNamedParameter($offset)))
			->andWhere($qb->expr()->gt('expire', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
	}

	/**
	 * Delete polls per deletion timestamp
	 */
	public function deleteArchivedPolls(int $offset): int {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->lt('deleted', $qb->createNamedParameter($offset)))
			->andWhere($qb->expr()->gt('deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		return $qb->executeStatement();
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
	protected function buildQuery(bool $detailed = true): IQueryBuilder {
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->groupBy(self::TABLE . '.id');

		$currentUserId = $this->userSession->getCurrentUserId();
		$currentUserParam = $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR);
		$pollGroupsAlias = 'poll_groups';

		$this->subQueryMaxDate($qb, self::TABLE);

		$this->joinUserRole($qb, self::TABLE, $currentUserParam);
		$this->joinGroupShares($qb, self::TABLE);
		$this->joinPollGroups($qb, self::TABLE, $pollGroupsAlias);
		$this->joinPollGroupShares($qb, $pollGroupsAlias, $currentUserParam, $pollGroupsAlias);
		$this->joinParticipantsCount($qb, self::TABLE);

		$this->subQueryVotesCount($qb, self::TABLE, $currentUserParam);

		if ($detailed) {
			// Is not relevant for the polls collection
			$this->subQueryVotesCount($qb, self::TABLE, $currentUserParam, Vote::VOTE_YES);
			$this->subQueryVotesCount($qb, self::TABLE, $currentUserParam, Vote::VOTE_NO);
			$this->subQueryVotesCount($qb, self::TABLE, $currentUserParam, Vote::VOTE_EVENTUALLY);
			$this->subQueryOrphanedVotesCount($qb, self::TABLE, $currentUserParam);
		}

		return $qb;
	}

	/**
	 * Joins shares to evaluate user role
	 *
	 * @param IQueryBuilder $qb the query builder to add the join to
	 * @param string $fromAlias the alias of the main poll table
	 * @param IParameter $currentUserParam the current user parameter to filter shares by user
	 * @param string $joinAlias the alias for the join, defaults to 'user_shares'
	 */
	protected function joinUserRole(
		IQueryBuilder &$qb,
		string $fromAlias,
		IParameter $currentUserParam,
		string $joinAlias = 'user_shares',
	): void {
		$emptyString = $qb->expr()->literal('');

		$qb->addSelect($qb->createFunction('coalesce(' . $joinAlias . '.type, ' . $emptyString . ') AS user_role'))
			->addGroupBy($joinAlias . '.type');

		$qb->selectAlias($joinAlias . '.locked', 'is_current_user_locked')
			->addGroupBy($joinAlias . '.locked');

		$qb->addSelect($qb->createFunction('coalesce(' . $joinAlias . '.token, ' . $emptyString . ') AS share_token'))
			->addGroupBy($joinAlias . '.token');

		$qb->leftJoin(
			$fromAlias,
			Share::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($joinAlias . '.poll_id', $fromAlias . '.id'),
				$qb->expr()->eq($joinAlias . '.user_id', $currentUserParam),
				$qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
			)
		);
	}

	/**
	 * Join group shares of this poll
	 *
	 * @param IQueryBuilder $qb the query builder to add the join to
	 * @param string $fromAlias the alias of the main poll table
	 * @param string $joinAlias the alias for the join, defaults to 'group_shares'
	 */
	protected function joinGroupShares(
		IQueryBuilder &$qb,
		string $fromAlias,
		string $joinAlias = 'group_shares',
	): void {

		SqlHelper::getConcatenatedArray(
			qb: $qb,
			concatColumn: $joinAlias . '.user_id',
			asColumn: 'group_shares',
			dbProvider: $this->db->getDatabaseProvider(),
		);

		$qb->leftJoin(
			$fromAlias,
			Share::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($joinAlias . '.poll_id', $fromAlias . '.id'),
				$qb->expr()->eq($joinAlias . '.type', $qb->expr()->literal(Share::TYPE_GROUP)),
				$qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
			)
		);
	}

	/**
	 * Joins poll groups, the poll belongs to
	 *
	 * @param IQueryBuilder $qb the query builder to add the join to
	 * @param string $fromAlias the alias of the main poll table
	 * @param string $joinAlias the alias for the join, defaults to 'poll_groups'
	 */
	protected function joinPollGroups(
		IQueryBuilder $qb,
		string $fromAlias,
		string $joinAlias = 'poll_groups',
	): void {

		SqlHelper::getConcatenatedArray(
			qb: $qb,
			concatColumn: $joinAlias . '.group_id',
			asColumn: 'poll_groups',
			dbProvider: $this->db->getDatabaseProvider(),
		);

		$qb->leftJoin(
			$fromAlias,
			PollGroup::RELATION_TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq(self::TABLE . '.id', $joinAlias . '.poll_id'),
			)
		);
	}

	/**
	 * Joins shares that are set for poll groups
	 * Poll group shares are meant to inherit access
	 * Higher access types will win. Currently poll groups are only availablke for
	 * authenticated users.
	 *
	 * Supported share types are User and Admin
	 * Groups, Teams will not work atm.
	 *
	 * @param IQueryBuilder $qb the query builder to add the join to
	 * @param string $fromAlias the alias of the main poll table
	 * @param IParameter $currentUserParam the current user parameter to filter shares by user
	 * @param string $pollGroupsAlias the alias of the poll groups table
	 * @param string $joinAlias the alias for the join, defaults to 'poll_group_shares'
	 */
	protected function joinPollGroupShares(
		IQueryBuilder $qb,
		string $fromAlias,
		IParameter $currentUserParam,
		string $pollGroupsAlias,
		string $joinAlias = 'poll_group_shares',
	): void {

		SqlHelper::getConcatenatedArray(
			qb: $qb,
			concatColumn: $joinAlias . '.type',
			asColumn: 'poll_group_user_shares',
			dbProvider: $this->db->getDatabaseProvider(),
		);

		$qb->leftJoin(
			$fromAlias,
			Share::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($joinAlias . '.group_id', $pollGroupsAlias . '.group_id'),
				$qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
				$qb->expr()->eq($joinAlias . '.user_id', $currentUserParam),
			)
		);
	}

	/**
	 * SubQuery the max option date for date polls
	 * the max value is null
	 * and adds the number of available options
	 *
	 * @param IQueryBuilder $qb the query builder to add the subquery to
	 * @param string $fromAlias the alias of the main poll table
	 */
	protected function subQueryMaxDate(
		IQueryBuilder &$qb,
		string $fromAlias,
	): void {
		$subQuery = $this->db->getQueryBuilder();

		$subQuery->select($subQuery->func()->max('options.timestamp'))
			->from(Option::TABLE, 'options')
			->where($subQuery->expr()->eq('options.poll_id', $fromAlias . '.id'))
			->andWhere($subQuery->expr()->eq('options.deleted', $subQuery->expr()->literal(0, IQueryBuilder::PARAM_INT)));

		$qb->selectAlias($qb->createFunction('(' . $subQuery->getSQL() . ')'), 'max_date');
	}

	/**
	 * SubQuery the user vote stats
	 * Adds the current user votes, yes, no, maybe and orphaned votes
	 * The result will be added to the main query as a subquery
	 *  - total count results in `current_user_votes`, if $answerFilter is null
	 *  - {$answerFilter} count results in `current_user_votes_{$answerFilter}`
	 *
	 * @param IQueryBuilder $qb the query builder to add the subquery to
	 * @param string $fromAlias the alias of the main poll table
	 * @param IParameter $currentUserParam the current user parameter to filter votes by user
	 * @param string|null $answerFilter the answer filter to apply, can be 'yes', 'no', 'maybe' or null for total votes
	 */
	protected function subQueryVotesCount(
		IQueryBuilder &$qb,
		string $fromAlias,
		IParameter $currentUserParam,
		?string $answerFilter = null,
	): void {
		$subAlias = 'votes';
		$alias = 'current_user_votes';

		$subQuery = $this->db->getQueryBuilder();
		$expr = $subQuery->expr();

		$subQuery->select($subQuery->func()->count($subAlias . '.id'))
			->from(Vote::TABLE, $subAlias)
			->where($expr->eq($subAlias . '.poll_id', $fromAlias . '.id'))
			->andWhere($expr->eq($subAlias . '.user_id', $currentUserParam));

		// filter by answer
		if ($answerFilter) {
			$subQuery->andWhere($expr->eq($subAlias . '.vote_answer', $qb->createNamedParameter($answerFilter, IQueryBuilder::PARAM_STR)));
			$alias = $alias . '_' . $answerFilter;
		}

		$qb->selectAlias($qb->createFunction('(' . $subQuery->getSQL() . ')'), $alias);
	}

	/**
	 * SubQuery the count of orphaned votes
	 * Orphaned votes are votes that do not have a matching option in the poll
	 * This is used to detect if a user has voted for an option that has been deleted
	 * and therefore the vote is orphaned.
	 *
	 * @param IQueryBuilder $qb the query builder to add the subquery to
	 * @param string $fromAlias the alias of the main poll table
	 * @param IParameter $currentUserParam the current user parameter to filter votes by user
	 */
	protected function subQueryOrphanedVotesCount(
		IQueryBuilder &$qb,
		string $fromAlias,
		IParameter $currentUserParam,
	): void {
		$subAlias = 'v';
		$optionAlias = 'o';
		$alias = 'current_user_orphaned_votes';

		$subQuery = $this->db->getQueryBuilder();
		$expr = $subQuery->expr();

		$subQuery->select($subQuery->func()->count($subAlias . '.id'))
			->from(Vote::TABLE, $subAlias)
			->leftJoin(
				$subAlias,
				Option::TABLE,
				$optionAlias,
				$expr->andX(
					$expr->eq($optionAlias . '.poll_id', $subAlias . '.poll_id'),
					$expr->eq($optionAlias . '.poll_option_hash', $subAlias . '.vote_option_hash'),
					$expr->eq($optionAlias . '.deleted', $expr->literal(0, IQueryBuilder::PARAM_INT))
				)
			)
			->where($expr->eq($subAlias . '.poll_id', $fromAlias . '.id'))
			->andWhere($expr->eq($subAlias . '.user_id', $currentUserParam))
			->andWhere($expr->isNull($optionAlias . '.id')); // orphaned!

		$qb->selectAlias($qb->createFunction('(' . $subQuery->getSQL() . ')'), $alias);
	}

	/**
	 * Join to count of participants in poll
	 *
	 * @param IQueryBuilder $qb the query builder to add the join to
	 * @param string $fromAlias the alias of the main poll table
	 * @param string $joinAlias the alias for the join, defaults to 'participants'
	 */
	protected function joinParticipantsCount(
		IQueryBuilder &$qb,
		string $fromAlias,
		string $joinAlias = 'participants',
	): void {
		$qb->leftJoin(
			$fromAlias,
			Vote::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($joinAlias . '.poll_id', $fromAlias . '.id'),
			)
		)
			->selectAlias($qb->createFunction('COUNT(DISTINCT(' . $joinAlias . '.user_id))'), 'participants_count');
	}
}
