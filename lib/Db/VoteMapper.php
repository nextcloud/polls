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
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Vote>
 */
class VoteMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'polls_votes', Vote::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findByPoll(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findByPollAndUser(int $pollId, string $userId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 */
	public function findSingleVote(int $pollId, string $optionText, string $userId): Vote {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('vote_option_text', $qb->createNamedParameter($optionText, IQueryBuilder::PARAM_STR)))
		   ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntity($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findParticipantsByPoll(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->selectDistinct('user_id')
		   ->from($this->getTableName())
		   ->where(
			   $qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   );

		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function findParticipantsVotes(int $pollId, string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	public function deleteById(int $voteId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
		   ->where($qb->expr()->eq('id', $qb->createNamedParameter($voteId, IQueryBuilder::PARAM_INT)))
		   ->execute();
	}

	public function deleteByPollAndUserId(int $pollId, string $userId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
		   ->execute();
	}

	public function deleteByPoll(int $pollId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->execute();
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function getYesVotesByParticipant(int $pollId, string $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
		   ->andWhere($qb->expr()->eq('vote_answer', $qb->createNamedParameter('yes', IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 * @psalm-return array<array-key, Vote>
	 */
	public function getYesVotesByOption(int $pollId, string $pollOptionText): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('vote_option_text', $qb->createNamedParameter($pollOptionText, IQueryBuilder::PARAM_STR)))
		   ->andWhere($qb->expr()->eq('vote_answer', $qb->createNamedParameter('yes', IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}


	public function removeDuplicates($output = null): int {
		$count = 0;
		try {
			$query = $this->db->getQueryBuilder();
			$query->select('id', 'poll_id', 'user_id', 'vote_option_text')
				->from($this->getTableName());
			$foundEntries = $query->execute();

			$delete = $this->db->getQueryBuilder();
			$delete->delete($this->getTableName())
				->where('id = :id');

			$entries2Keep = [];

			while ($row = $foundEntries->fetch()) {
				$currentRecord = [
					$row['poll_id'],
					$row['user_id'],
					$row['vote_option_text']
				];
				if (in_array($currentRecord, $entries2Keep)) {
					$delete->setParameter('id', $row['id']);
					$delete->execute();
					$count++;
				} else {
					$entries2Keep[] = $currentRecord;
				}
			}
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_DATABASE_OBJECT_NOT_FOUND) {
				// ignore silently
			}
			throw $e;
		}

		if ($output && $count) {
			$output->info('Removed ' . $count . ' duplicate records from ' . $this->getTableName());
		}

		return $count;
	}

	public function renameUserId(string $userId, string $replacementName): void {
		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('user_id', $query->createNamedParameter($replacementName))
			->where($query->expr()->eq('user_id', $query->createNamedParameter($userId)))
			->execute();
	}
}
