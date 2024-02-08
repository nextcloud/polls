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

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapperWithUser<Vote>
 */
class VoteMapper extends QBMapperWithUser {
	public const TABLE = Vote::TABLE;

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		IDBConnection $db,
		private UserMapper $userMapper,
		private LoggerInterface $logger,
	) {
		parent::__construct($db, self::TABLE, Vote::class);
	}

	public function update(Entity $entity): Vote {
		$entity->setVoteOptionHash(hash('md5', $entity->getPollId() . $entity->getUserId() . $entity->getVoteOptionText()));
		$entity = parent::update($entity);
		return $this->find($entity->getId());
	}
	
	public function insert(Entity $entity): Vote {
		$entity->setVoteOptionHash(hash('md5', $entity->getPollId() . $entity->getUserId() . $entity->getVoteOptionText()));
		$entity = parent::insert($entity);
		return $this->find($entity->getId());
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')->from($this->getTableName());
		return $this->findEntities($qb);
	}
	
	
	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findByPoll(int $pollId): array {
		$qb = $this->buildQuery();
		$qb->andWhere($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findByPollAndUser(int $pollId, string $userId): array {
		$qb = $this->buildQuery();
		$qb->andWhere($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq(self::TABLE . '.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 */
	public function findSingleVote(int $pollId, string $optionText, string $userId): Vote {
		$qb = $this->buildQuery();
		$qb->andWhere($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
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

		$qb->selectDistinct([self::TABLE . '.user_id', self::TABLE . '.poll_id'])
			->from($this->getTableName(), self::TABLE)
			->where(
				$qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
			);
		$qb->addGroupBy(self::TABLE . '.user_id', self::TABLE . '.poll_id');

		return $this->findEntities($qb);
	}


	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findParticipantsVotes(int $pollId, string $userId): array {
		$qb = $this->buildQuery();
		$qb->andWhere($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq(self::TABLE . '.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	public function deleteByPollAndUserId(int $pollId, string $userId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		$qb->executeStatement();
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function getYesVotesByParticipant(int $pollId, string $userId): array {
		$qb = $this->buildQuery();
		$qb->andWhere($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
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
		$qb->andWhere($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
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
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findOrphanedByPollandUser(int $pollId, string $userId): array {
		$qb = $this->buildQuery(findOrphaned: true);
		$qb->andWhere($qb->expr()->eq(self::TABLE . '.poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq(self::TABLE . '.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * Build the enhanced query with joined tables
	 */
	protected function find(int $id): Vote {
		$qb = $this->buildQuery();
		$qb->andWhere($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			// Possible orphaned vote entry without option, try to get it directly from the table
			$this->logger->info('Possibly orphaned vote found, try fallback search.', ['vote_id' => $id]);
			$qb = $this->db->getQueryBuilder();
			$qb->select(self::TABLE . '.*')
				->from($this->getTableName(), self::TABLE)
				->where($qb->expr()->eq(self::TABLE . '.id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
			return $this->findEntity($qb);
		}
	}

	protected function buildQuery(bool $findOrphaned = false): IQueryBuilder {
		$qb = $this->db->getQueryBuilder();

		$qb->select(self::TABLE . '.*')
			->from($this->getTableName(), self::TABLE);

		$optionAlias = $this->joinOption($qb, self::TABLE);
		
		
		if ($findOrphaned) {
			$qb->where($qb->expr()->isNull($optionAlias . '.id'));
		} else {
			$qb->where($qb->expr()->isNotNull($optionAlias . '.id'));
		}
		$anonAlias = $this->joinAnon($qb, self::TABLE);

		$qb->groupby(self::TABLE . '.id', $optionAlias . '.id', $anonAlias . '.anonymous');
		
		return $qb;
	}

	/**
	 * Joins options to restrict query to votes with actually undeleted options
	 * Avoid orphaned votes
	 */
	protected function joinOption(IQueryBuilder &$qb, string $fromAlias): string {
		$joinAlias = 'options';
		
		$qb->selectAlias($joinAlias . '.id', 'option_id');

		$qb->leftJoin(
			$fromAlias,
			Option::TABLE,
			$joinAlias,
			$qb->expr()->andX(
				$qb->expr()->eq($joinAlias . '.poll_id', $fromAlias . '.poll_id'),
				$qb->expr()->eq($joinAlias . '.poll_option_text', $fromAlias . '.vote_option_text'),
				$qb->expr()->eq($joinAlias . '.deleted', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)),
			)
		);

		return $joinAlias;
	}
}
