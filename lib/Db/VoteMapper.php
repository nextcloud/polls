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

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;
use Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * @template-extends QBMapper<Vote>
 */
class VoteMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'polls_votes', '\OCA\Polls\Db\Vote');
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 */
	public function findByPoll(int $pollId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote[]
	 */
	public function findByPollAndUser(int $pollId, string $userId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Vote
	 */
	public function findSingleVote(int $pollId, string $optionText, string $userId) {
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
	 */
	public function findParticipantsByPoll(int $pollId) {
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
	 *
	 * @return Vote[]
	 *
	 * @psalm-return array<array-key, Vote>
	 */
	public function findParticipantsVotes(int $pollId, $userId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
		return $this->findEntities($qb);
	}

	public function deleteByPollAndUser(int $pollId, string $userId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
		   ->execute();
	}

	public function deleteByPoll($pollId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->execute();
	}

	public function countYesVotes(string $userId, int $pollId): int {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
		   ->andWhere($qb->expr()->eq('vote_answer', $qb->createNamedParameter('yes', IQueryBuilder::PARAM_STR)));
		return count($this->findEntities($qb));
	}

	/**
	 * @return void
	 */
	public function removeDuplicates() {
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
				} else {
					$entries2Keep[] = $currentRecord;
				}
			}
		} catch (TableNotFoundException $e) {
			// ignore
		}
	}
}
