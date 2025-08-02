<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use OCA\Polls\UserSession;
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
	public function get(int $id, bool $getDeleted = false, bool $withRoles = false): Poll {
		$qb = $this->db->getQueryBuilder();
		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->where($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->groupBy(self::TABLE . '.id');

		if (!$getDeleted) {
			$qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		}

		if ($withRoles) {
			$pollGroupsAlias = 'poll_groups';
			$currentUserId = $this->userSession->getCurrentUserId();
			$currentUserParam = $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR);

			$this->subQueryMaxDate($qb, self::TABLE);

			$this->joinUserRole($qb, self::TABLE, $currentUserParam);
			$this->joinGroupShares($qb, self::TABLE);
			$this->joinPollGroups($qb, self::TABLE, $pollGroupsAlias);
			$this->joinPollGroupShares($qb, $pollGroupsAlias, $currentUserParam, $pollGroupsAlias);
		}
		return $this->findEntity($qb);
	}

	/**
	 * Get poll with joins for operations with permissions and user informations
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
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->like('misc_settings', $qb->createNamedParameter($autoReminderSearchString, IQueryBuilder::PARAM_STR)))
			->andwhere($qb->expr()->eq('deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));

		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Poll[]
	 */
	public function findForMe(string $userId): array {
		$qb = $this->buildQuery(detailed: false);
		$qb->where($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)))
			->orWhere($qb->expr()->eq(self::TABLE . '.owner', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
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
	protected function buildQuery($detailed = true): IQueryBuilder {
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

		TableManager::getConcatenatedArray(
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

		TableManager::getConcatenatedArray(
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

		TableManager::getConcatenatedArray(
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
					$expr->eq($optionAlias . '.poll_option_text', $subAlias . '.vote_option_text'),
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
