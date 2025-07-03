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
	public function get(int $id, bool $getDeleted = false): Poll {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		if (!$getDeleted) {
			$qb->andWhere($qb->expr()->eq('deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
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
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)))
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
		$qb = $this->buildQuery();
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
	protected function buildQuery(): IQueryBuilder {
		$currentUserId = $this->userSession->getCurrentUserId();
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->groupBy(self::TABLE . '.id');

		$paramUser = $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR);

		$qb->selectAlias($qb->createFunction('(' . $this->subQueryOrphanedVotesCount(self::TABLE, $paramUser)->getSQL() . ')'), 'current_user_orphaned_votes');
		$qb->selectAlias($qb->createFunction('(' . $this->subQueryParticipantsCount(self::TABLE)->getSQL() . ')'), 'participants_count');

		$pollGroupsAlias = 'poll_groups';
		$this->joinOptions($qb, self::TABLE);
		$this->joinUserRole($qb, self::TABLE, $currentUserId);
		$this->joinGroupShares($qb, self::TABLE);
		$this->joinPollGroups($qb, self::TABLE, $pollGroupsAlias);
		$this->joinUserSharesfromPollGroups($qb, $pollGroupsAlias, $currentUserId, $pollGroupsAlias);
		$this->joinVotesCount($qb, $currentUserId, self::TABLE);
		return $qb;
	}

	/**
	 * Joins shares to evaluate user role
	 */
	protected function joinUserRole(
		IQueryBuilder &$qb,
		string $fromAlias,
		string $currentUserId,
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
				$qb->expr()->eq($fromAlias . '.id', $joinAlias . '.poll_id'),
				$qb->expr()->eq($joinAlias . '.user_id', $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
			)
		);

	}

	/**
	 * Join group shares of this poll
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
				$qb->expr()->eq($fromAlias . '.id', $joinAlias . '.poll_id'),
				$qb->expr()->eq($joinAlias . '.type', $qb->expr()->literal(Share::TYPE_GROUP)),
				$qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
			)
		);
	}

	/**
	 * Joins poll groups, the poll belongs to
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
	 */
	protected function joinUserSharesfromPollGroups(
		IQueryBuilder $qb,
		string $fromAlias,
		string $currentUserId,
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
				$qb->expr()->eq($joinAlias . '.user_id', $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR)),
			)
		);
	}

	/**
	 * Joins options to evaluate min and max option date for date polls
	 * if text poll or no options are set,
	 * the min value is the current time,
	 * the max value is null
	 * and adds the number of available options
	 */
	protected function joinOptions(
		IQueryBuilder &$qb,
		string $fromAlias,
		string $joinAlias = 'options',
	): void {

		$zero = $qb->expr()->literal(0, IQueryBuilder::PARAM_INT);
		$saveMin = $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT);

		$qb->addSelect($qb->createFunction('coalesce(MAX(' . $joinAlias . '.timestamp), ' . $zero . ') AS max_date'))
			->addSelect($qb->createFunction('coalesce(MIN(' . $joinAlias . '.timestamp), ' . $saveMin . ') AS min_date'))
			->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN ' . $joinAlias . '.owner != \'\' THEN 1 END)) AS proposals_count'));
		$qb->selectAlias($qb->func()->count($joinAlias . '.id'), 'optionsCount');

		$qb->leftJoin(
			$fromAlias,
			Option::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($fromAlias . '.id', $joinAlias . '.poll_id'),
				$qb->expr()->eq($joinAlias . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)),
			),
		);
	}

	/**
	 * Subquery for votes count
	 */
	protected function subQueryVotesCount(
		string $fromAlias,
		IParameter $currentUserId,
		?IParameter $answerFilter = null,
		$subAlias = 'user_vote_sub',
	): IQueryBuilder {

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
	 * Joins votes to count votes per option and answer
	 */
	protected function joinVotesCount(
		IQueryBuilder &$qb,
		string $currentUserId,
		string $fromAlias,
		bool $hideResults = false,
		string $joinAlias = 'votes',
	): void {

		$qb->leftJoin(
			$fromAlias,
			Vote::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($joinAlias . '.poll_id', $fromAlias . '.id'),
				$qb->expr()->eq($joinAlias . '.user_id', $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR)),
			)
		)
			// Count number of votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(' . $joinAlias . '.id)) AS current_user_votes'))
			// Count number of yes votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN ' . $joinAlias . '.vote_answer = \'yes\' THEN ' . $joinAlias . '.id END)) AS current_user_votes_yes'))
			// Count number of no votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN ' . $joinAlias . '.vote_answer = \'no\' THEN ' . $joinAlias . '.id END)) AS current_user_votes_no'))
			// Count number of maybe votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN ' . $joinAlias . '.vote_answer = \'maybe\' THEN ' . $joinAlias . '.id END)) AS current_user_votes_maybe'))
			// inject if the votes should be hidden
			->addSelect($qb->createFunction(intval(!$hideResults) . ' as show_results'));
	}

	/**
	 * Subquery for count of orphaned votes
	 */
	protected function subQueryOrphanedVotesCount(
		string $fromAlias,
		IParameter $currentUserId,
		$subAlias = 'user_vote_sub',
		$subJoinAlias = 'vote_options_join',
	): IQueryBuilder {

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
	/**
	 * Subquery for count of orphaned votes
	 */
	protected function subQueryParticipantsCount(
		string $fromAlias,
		$subAlias = 'user_vote_sub',
	): IQueryBuilder {
		$subQuery = $this->db->getQueryBuilder();
		$subQuery->select($subQuery->createFunction('COUNT(DISTINCT ' . $subAlias . '.user_id)'))
			->from(Vote::TABLE, $subAlias)
			->where($subQuery->expr()->eq($subAlias . '.poll_id', $fromAlias . '.id'));
		return $subQuery;
	}
}
