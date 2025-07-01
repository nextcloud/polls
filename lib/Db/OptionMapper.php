<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use OCA\Polls\UserSession;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapperWithUser<Option>
 */
class OptionMapper extends QBMapperWithUser {
	public const TABLE = Option::TABLE;

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 * @psalm-suppress UnusedProperty
	 */
	public function __construct(
		IDBConnection $db,
		private UserSession $userSession,
	) {
		parent::__construct($db, Option::TABLE, Option::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Option[]
	 * @psalm-return array<array-key, Option>
	 */
	public function getAll(bool $includeNull = false): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')->from($this->getTableName());

		if (!$includeNull) {
			$qb->where($qb->expr()->isNotNull(self::TABLE . '.poll_id'));
		}

		return $this->findEntities($qb);
	}

	/**
	 * @return Option[]
	 * @param int $pollId
	 * @param bool $hideResults Whether the results should be hidden
	 * @param bool $getDeleted also search for deleted options
	 * @psalm-return array<array-key, Option>
	 */
	public function findByPoll(int $pollId, bool $hideResults = false, bool $getDeleted = false): array {
		$qb = $this->buildQuery($hideResults);
		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));
		if (!$getDeleted) {
			$qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		}

		return $this->findEntities($qb);
	}
	/**
	 * @return Option
	 * @param int $pollId
	 * @param string $pollOptionText option text
	 * @param bool $getDeleted also search for deleted options
	 */
	public function findByPollAndText(int $pollId, string $pollOptionText, bool $getDeleted = false): Option {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq(self::TABLE . '.poll_option_text', $qb->createNamedParameter($pollOptionText, IQueryBuilder::PARAM_STR)));
		if (!$getDeleted) {
			$qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));
		}

		return $this->findEntity($qb);
	}

	/**
	 * @return Option
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 */
	public function find(int $id): Option {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$qb->andWhere($qb->expr()->isNotNull(self::TABLE . '.poll_id'));

		return $this->findEntity($qb);
	}

	/**
	 * @return Option[]
	 * @psalm-return array<array-key, Option>
	 */
	public function findConfirmed(int $pollId): array {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->gt(self::TABLE . '.confirmed', $qb->expr()->literal(0, IQueryBuilder::PARAM_INT)));

		return $this->findEntities($qb);
	}

	/**
	 * @return (int|null)[]
	 */
	public function getOrderBoundaries(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->selectAlias($qb->func()->max('order'), 'max')
			->selectAlias($qb->func()->min('order'), 'min')
			->from($this->getTableName())
			->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));

		return $qb->executeQuery()->fetchAll()[0];
	}


	/**
	 * @return Option[]
	 */
	public function findOptionsWithDuration(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('duration', $qb->createNamedParameter(86400, IQueryBuilder::PARAM_INT))
			)
			->orderBy('order', 'ASC');

		return $this->findEntities($qb);
	}

	public function renameUserId(string $userId, string $replacementName, ?int $pollId = null): void {
		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('owner', $query->createNamedParameter($replacementName))
			->where($query->expr()->eq('owner', $query->createNamedParameter($userId)));

		if ($pollId !== null) {
			$query->andWhere($query->expr()->eq('poll_id', $query->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));
		}

		$query->executeStatement();
	}

	public function purgeDeletedOptions(int $offset): int {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->andWhere(
				$query->expr()->gt('deleted', $query->expr()->literal(0, IQueryBuilder::PARAM_INT))
			)
			->andWhere(
				$query->expr()->lt('deleted', $query->expr()->literal($offset, IQueryBuilder::PARAM_INT))
			);
		return $query->executeStatement();
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
	 * Build the enhanced query with joined tables
	 * @param bool $hideResults Whether poll results are defined as beeing hidden
	 *                          injects the poll permission allowdSeeResults into the query
	 */
	protected function buildQuery(bool $hideResults = false): IQueryBuilder {
		$currentUserId = $this->userSession->getCurrentUserId();
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->groupBy(self::TABLE . '.id')
			->orderBy('order', 'ASC');


		$this->joinVotesCount($qb, self::TABLE, $hideResults);
		$this->joinPollForLimits($qb, self::TABLE);
		$this->joinCurrentUserVote($qb, self::TABLE, $currentUserId);
		$this->joinCurrentUserVoteCount($qb, self::TABLE, $currentUserId);
		$this->joinAnon($qb, self::TABLE);
		$this->joinShareRole($qb, self::TABLE, $currentUserId);


		return $qb;
	}

	/**
	 * Joins votes to count votes per option and answer
	 */
	protected function joinVotesCount(IQueryBuilder &$qb, string $fromAlias, bool $hideResults = false): void {
		$joinAlias = 'votes';
		$qb->leftJoin(
			$fromAlias,
			Vote::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($fromAlias . '.poll_id', $joinAlias . '.poll_id'),
				$qb->expr()->eq($fromAlias . '.poll_option_text', $joinAlias . '.vote_option_text'),
			)
		)
			// Count number of votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(' . $joinAlias . '.id)) AS count_option_votes'))
			// Count number of yes votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN ' . $joinAlias . '.vote_answer = \'yes\' THEN ' . $joinAlias . '.id END)) AS votes_yes'))
			// Count number of no votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN ' . $joinAlias . '.vote_answer = \'no\' THEN ' . $joinAlias . '.id END)) AS votes_no'))
			// Count number of maybe votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN ' . $joinAlias . '.vote_answer = \'maybe\' THEN ' . $joinAlias . '.id END)) AS votes_maybe'))
			// inject if the votes should be hidden
			->addSelect($qb->createFunction(intval(!$hideResults) . ' as show_results'));
	}

	/**
	 * Joins poll to fetch option_limit and vote_limit
	 */
	protected function joinPollForLimits(IQueryBuilder &$qb, string $fromAlias): void {
		$joinAlias = 'limits';

		// force value into a MIN function to avoid grouping errors
		$qb->selectAlias($qb->func()->min($joinAlias . '.option_limit'), 'option_limit')
			->selectAlias($qb->func()->min($joinAlias . '.vote_limit'), 'vote_limit');

		$qb->leftJoin(
			$fromAlias,
			Poll::TABLE,
			$joinAlias,
			$qb->expr()->eq($joinAlias . '.id', $fromAlias . '.poll_id'),
		);
	}

	/**
	 * Joins votes to get the current user's answer to this option
	 */
	protected function joinCurrentUserVote(IQueryBuilder &$qb, string $fromAlias, string $currentUserId): void {
		$joinAlias = 'user_vote';

		// force value into a MIN function to avoid grouping errors
		$qb->selectAlias($qb->func()->min($joinAlias . '.vote_answer'), 'user_vote_answer');

		$qb->leftJoin(
			$fromAlias,
			Vote::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($joinAlias . '.poll_id', $fromAlias . '.poll_id'),
				$qb->expr()->eq($joinAlias . '.user_id', $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq($joinAlias . '.vote_option_text', $fromAlias . '.poll_option_text'),
			)
		);
	}

	/**
	 * Joins votes to be able to check against polls_polls.vote_limit of the current user
	 * in other words: returns all votes of current user and count them
	 */
	protected function joinCurrentUserVoteCount(IQueryBuilder &$qb, string $fromAlias, string $currentUserId): void {
		$joinAlias = 'votes_user';

		// Count yes votes of the user in this poll
		$qb->addSelect($qb->createFunction('COUNT(DISTINCT(votes_user.id)) AS user_count_yes_votes'));

		$qb->leftJoin(
			$fromAlias,
			Vote::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($joinAlias . '.poll_id', $fromAlias . '.poll_id'),
				$qb->expr()->eq($joinAlias . '.user_id', $qb->createNamedParameter($currentUserId, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq($joinAlias . '.vote_answer', $qb->createNamedParameter(Vote::VOTE_YES, IQueryBuilder::PARAM_STR)),
			)
		);
	}

}
