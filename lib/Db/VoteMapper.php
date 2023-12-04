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

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Vote>
 */
class VoteMapper extends QBMapper {
	public const TABLE = Vote::TABLE;

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE, Vote::class);
	}

	public function update(Entity $entity): Entity {
		$entity->setVoteOptionHash(hash('md5', $entity->getPollId() . $entity->getUserId() . $entity->getVoteOptionText()));
		return parent::update($entity);
	}

	public function insert(Entity $entity): Entity {
		$entity->setVoteOptionHash(hash('md5', $entity->getPollId() . $entity->getUserId() . $entity->getVoteOptionText()));
		return parent::insert($entity);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function getAll(): array {
		$qb = $this->buildQuery();
		return $this->findEntities($qb);
	}
	
	
	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findByPoll(int $pollId): array {
		$qb = $this->buildQuery();
		
		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findByPollAndUser(int $pollId, string $userId): array {
		$qb = $this->buildQuery();

		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq(self::TABLE . '.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 */
	public function findSingleVote(int $pollId, string $optionText, string $userId): Vote {
		$qb = $this->buildQuery();

		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq(self::TABLE . '.vote_option_text', $qb->createNamedParameter($optionText, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq(self::TABLE . '.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findParticipantsByPoll(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->selectDistinct(['user_id', 'poll_id'])
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
			);
		$this->joinDisplayNameFromShare($qb, self::TABLE);

		return $this->findEntities($qb);
	}


	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findParticipantsVotes(int $pollId, string $userId): array {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq(self::TABLE . '.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	public function deleteByPollAndUserId(int $pollId, string $userId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->executeStatement();
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function getYesVotesByParticipant(int $pollId, string $userId): array {
		$qb = $this->buildQuery();
		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq(self::TABLE . '.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq(self::TABLE . '.vote_answer', $qb->createNamedParameter(Vote::VOTE_YES, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function getYesVotesByOption(int $pollId, string $pollOptionText): array {
		$qb = $this->buildQuery();

		$qb->where($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq(self::TABLE . '.vote_option_text', $qb->createNamedParameter($pollOptionText, IQueryBuilder::PARAM_STR)))
			->andWhere($qb->expr()->eq(self::TABLE . '.vote_answer', $qb->createNamedParameter(Vote::VOTE_YES, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	public function renameUserId(string $userId, string $replacementName): void {
		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('user_id', $query->createNamedParameter($replacementName))
			->where($query->expr()->eq('user_id', $query->createNamedParameter($userId)))
			->executeStatement();
	}

	public function fixVoteOptionText(int $pollId, int $optionId, string $searchOptionText, string $replaceOptionText): void {
		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('vote_option_text', $query->createNamedParameter($replaceOptionText))
			->where($query->expr()->eq('vote_option_text', $query->createNamedParameter($searchOptionText)))
			->andWhere($query->expr()->eq('poll_id', $query->createNamedParameter($pollId)))
			->andWhere($query->expr()->eq('vote_option_id', $query->createNamedParameter($optionId)))
			->executeStatement();
	}


	/**
	 * Build the enhanced query with joined tables
	 */
	protected function buildQuery(): IQueryBuilder {
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE)
			->groupby(self::TABLE . '.id');

		$this->joinDisplayNameFromShare($qb, self::TABLE);
		return $qb;
	}

	/**
	 * Joins shares to fetch displayName from shares
	 */
	protected function joinDisplayNameFromShare(IQueryBuilder &$qb, string $fromAlias): void {
		$joinAlias = 'shares';
		// force value into a MIN function to avoid grouping errors
		$qb->selectAlias($qb->func()->min($joinAlias . '.display_name'), 'display_name');
		$qb->leftJoin(
			$fromAlias,
			Share::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq(self::TABLE . '.poll_id', $joinAlias . '.poll_id'),
				$qb->expr()->eq(self::TABLE . '.user_id', $joinAlias . '.user_id'),
			)
		);
	}

}
