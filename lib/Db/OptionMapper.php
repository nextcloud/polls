<?php
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
use OCP\ISession;

/**
 * @template-extends QBMapper<Option>
 */
class OptionMapper extends QBMapper {
	public const TABLE = Option::TABLE;

	public function __construct(
		IDBConnection $db,
		private ISession $session,
		private UserMapper $userMapper,
		private ?string $userId,
	) {
		parent::__construct($db, Option::TABLE, Option::class);
	}

	/**
	 * Alias of update with joined and computed attributes
	 * @param Option $option Option to update
	 * @param string $currentUser The current user, needed for checking limits
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
	 * @param string $currentUser The current user, needed for checking limits
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
	 * Build the enhanced query with joined tables
	 * @param string $currentUser The current user, needed for checking limits
	 */
	private function buildQuery(bool $hideVotes = false) : IQueryBuilder {
		$currentUser = $this->userMapper->getCurrentUserId();
		$qb = $this->db->getQueryBuilder();

		$qb->select('options.*')
			->from($this->getTableName(), 'options')
			->groupBy('options.id')
			->orderBy('order', 'ASC');

		// +++++++++
		// The joins
		// +++++++++

		// Votes tablejoin
		// left join votes of this option to be able to count the votes of this option (seperated by answer)
		if ($hideVotes) {
			// $qb->leftJoin(
			// 	'options',
			// 	Vote::TABLE,
			// 	'votes',
			// 	$qb->expr()->andX(
			// 		$qb->expr()->eq('options.poll_id', 'votes.poll_id'),
			// 		$qb->expr()->eq('options.poll_option_text', 'votes.vote_option_text'),
			// 	)
			// )
			// Count number of votes for this option
			$qb->addSelect($qb->createFunction('0 AS count_option_votes'))
				->addSelect($qb->createFunction('0 AS votes_yes'))
				->addSelect($qb->createFunction('0 AS votes_no'))
				->addSelect($qb->createFunction('0 AS votes_maybe'));
		} else {
			$qb->leftJoin(
				'options',
				Vote::TABLE,
				'votes',
				$qb->expr()->andX(
					$qb->expr()->eq('options.poll_id', 'votes.poll_id'),
					$qb->expr()->eq('options.poll_option_text', 'votes.vote_option_text'),
				)
			)
			// Count number of votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(votes.id)) AS count_option_votes'))
			// Count number of yes votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN votes.vote_answer = "yes" THEN votes.id END)) AS votes_yes'))
			// Count number of no votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN votes.vote_answer = "no" THEN votes.id END)) AS votes_no'))
			// Count number of maybe votes for this option
			->addSelect($qb->createFunction('COUNT(DISTINCT(CASE WHEN votes.vote_answer = "maybe" THEN votes.id END)) AS votes_maybe'));
		}
		// Polls table join
		// left join to fetch option_limit and vote_limit of the poll the option belongs to
		$qb->leftJoin(
			'options',
			Poll::TABLE,
			'polls',
			$qb->expr()->eq('options.poll_id', 'polls.id'),
		)
		->addSelect('polls.option_limit', 'polls.vote_limit')

		// Votes table join (#1)
		// left join votes of this option to get the current user's answer to this option
		->leftJoin(
			'options',
			Vote::TABLE,
			'option_vote_user',
			$qb->expr()->andX(
				$qb->expr()->eq('options.poll_id', 'option_vote_user.poll_id'),
				$qb->expr()->eq('options.poll_option_text', 'option_vote_user.vote_option_text'),
				$qb->expr()->eq('option_vote_user.user_id', $qb->createNamedParameter($currentUser, IQueryBuilder::PARAM_STR)),
			)
		)
		->addSelect('option_vote_user.vote_answer AS user_vote_answer')

		// Votes table join (#2)
		// left join votes of user to be able to check against polls_polls.vote_limit
		// in other words: returns all votes of current user and count them
		->leftJoin(
			'options',
			Vote::TABLE,
			'votes_user',
			$qb->expr()->andX(
				$qb->expr()->eq('options.poll_id', 'votes_user.poll_id'),
				$qb->expr()->eq('votes_user.user_id', $qb->createNamedParameter($currentUser, IQueryBuilder::PARAM_STR)),
				$qb->expr()->eq('votes_user.vote_answer', $qb->createNamedParameter(Vote::VOTE_YES, IQueryBuilder::PARAM_STR)),
			)
		)

		// Count yes votes of the user in this poll
		->addSelect($qb->createFunction('COUNT(DISTINCT(votes_user.id)) AS user_count_yes_votes'));
		return $qb;

	}

	/**
	 * @return Option[]
	 * @param string $currentUser The current user, needed for checking limits
	 * @psalm-return array<array-key, Option>
	 */
	public function findByPoll(int $pollId, bool $hideResults = false): array {
		$qb = $this->buildQuery($hideResults);
		$qb->where($qb->expr()->eq('options.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));

		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @param string $currentUser The current user, needed for checking limits
	 */
	public function find(int $id): Option {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq('options.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * @return Option[]
	 * @psalm-return array<array-key, Option>
	 */
	public function findConfirmed(int $pollId): array {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq('options.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->gt('options.confirmed', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));

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
}
