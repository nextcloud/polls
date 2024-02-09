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

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ISession;

/**
 * @template-extends QBMapperWithUser<Option>
 */
class OptionMapper extends QBMapperWithUser {
	public const TABLE = Option::TABLE;

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		IDBConnection $db,
		private ISession $session,
		private UserMapper $userMapper,
	) {
		parent::__construct($db, Option::TABLE, Option::class);
	}

	/**
	 * Alias of update with joined and computed attributes
	 * @param Option $option Option to update
	 */
	public function change(Option $option): Option {
		$option->updatePollOptionText();
		if ($option->getTimestamp() > 0) {
			$option->setOrder($option->getTimestamp());
		}
		$option->setPollOptionHash(hash('md5', $option->getPollId() . $option->getPollOptionText() . $option->getTimestamp()));
		return $this->find($this->update($option)->getId());
	}

	/**
	 * Alias of insert with enhanced entity
	 * @param Option $option Option to insert
	 */
	public function add(Option $option): Option {
		$option->updatePollOptionText();
		if ($option->getTimestamp() > 0) {
			$option->setOrder($option->getTimestamp());
		}
		$option->setPollOptionHash(hash('md5', $option->getPollId() . $option->getPollOptionText() . $option->getTimestamp()));
		return $this->find($this->insert($option)->getId());
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Option[]
	 * @psalm-return array<array-key, Option>
	 */
	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')->from($this->getTableName());
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
			$qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
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
			$qb->andWhere($qb->expr()->eq(self::TABLE . '.deleted', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
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

		return $this->findEntity($qb);
	}

	/**
	 * @return Option[]
	 * @psalm-return array<array-key, Option>
	 */
	public function findConfirmed(int $pollId): array {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->gt(self::TABLE . '.confirmed', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));

		return $this->findEntities($qb);
	}

	/**
	 * @return (int|null)[]
	 */
	public function findDateBoundaries(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->selectAlias($qb->func()->min('timestamp'), 'min')
			->selectAlias($qb->func()->max('timestamp'), 'max')
			->from($this->getTableName())
			->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));

		return $qb->executeQuery()->fetchAll()[0];
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

	public function renameUserId(string $userId, string $replacementName): void {
		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('owner', $query->createNamedParameter($replacementName))
			->where($query->expr()->eq('owner', $query->createNamedParameter($userId)))
			->executeStatement();
	}

	public function purgeDeletedOptions(int $offset): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->andWhere(
				$query->expr()->gt('deleted', $query->createNamedParameter(0))
			)
			->andWhere(
				$query->expr()->lt('deleted', $query->createNamedParameter($offset))
			);
		$query->executeStatement();
	}

	/**
	 * Build the enhanced query with joined tables
	 * @param bool $hideVotes Whether the votes should be hidden, skips vote counting
	 */
	protected function buildQuery(bool $hideVotes = false): IQueryBuilder {
		$currentUserId = $this->userMapper->getCurrentUser()->getId();
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->orderBy('order', 'ASC');

		$this->joinVotesCount($qb, self::TABLE, $hideVotes);
		$this->joinPollForLimits($qb, self::TABLE);
		$this->joinCurrentUserVote($qb, self::TABLE, $currentUserId);
		$this->joinCurrentUserVoteCount($qb, self::TABLE, $currentUserId);
		$anonAlias = $this->joinAnon($qb, self::TABLE);

		$qb->groupby(
			self::TABLE . '.id',
			$anonAlias . '.anonymous',
			$anonAlias . '.owner',
			$anonAlias . '.show_results',
			$anonAlias . '.expire',
		);

		return $qb;
	}

	/**
	 * Joins votes to count votes per option and answer
	 */
	protected function joinVotesCount(IQueryBuilder &$qb, string $fromAlias, bool $hideVotes = false): void {
		$joinAlias = 'votes';
		if ($hideVotes) {
			// hide all vote counts
			$qb->addSelect($qb->createFunction('0 AS count_option_votes'))
				->addSelect($qb->createFunction('0 AS votes_yes'))
				->addSelect($qb->createFunction('0 AS votes_no'))
				->addSelect($qb->createFunction('0 AS votes_maybe'));
		} else {
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
				->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN ' . $joinAlias . '.vote_answer = \'maybe\' THEN ' . $joinAlias . '.id END)) AS votes_maybe'));
		}
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
