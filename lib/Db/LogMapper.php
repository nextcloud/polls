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

use Doctrine\DBAL\Exception\DatabaseObjectNotFoundException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;

/**
 * @template-extends QBMapper<Log>
 */
class LogMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'polls_log', Log::class);
	}

	public function find(int $id): Log {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
			   $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
		   );

		return $this->findEntity($qb);
	}

	/**
	 * @return Log[]
	 * @psalm-return array<array-key, Log>
	 */
	public function findByPollId(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
			   $qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   );

		return $this->findEntities($qb);
	}

	/**
	 * @return Log[]
	 * @psalm-return array<array-key, Log>
	 */
	public function findUnprocessed(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
			   $qb->expr()->eq('processed', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT))
		   );

		return $this->findEntities($qb);
	}

	/**
	 * @return Log[]
	 * @psalm-return array<array-key, Log>
	 */
	public function findUnprocessedPolls(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->selectDistinct('poll_id')
			->from($this->getTableName())
			->where($qb->expr()->eq('processed', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		return $this->findEntities($qb);
	}

	public function getLastRecord(int $pollId): Log {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->setMaxResults(1)
			->orderBy('id', 'DESC');

		return $this->findEntity($qb);
	}

	public function removeDuplicates(?IOutput $output = null): int {
		$count = 0;
		try {
			// remove duplicates from oc_polls_log
			// preserve the first entry
			$query = $this->db->getQueryBuilder();
			$query->select('id', 'processed', 'poll_id', 'user_id', 'message_id')
				->from($this->getTableName());
			$foundEntries = $query->execute();

			$delete = $this->db->getQueryBuilder();
			$delete->delete($this->getTableName())
				->where('id = :id');

			$entries2Keep = [];

			while ($row = $foundEntries->fetch()) {
				$currentRecord = [
					$row['processed'],
					$row['poll_id'],
					$row['user_id'],
					$row['message_id']
				];
				if (in_array($currentRecord, $entries2Keep)) {
					$delete->setParameter('id', $row['id']);
					$delete->execute();
					$count++;
				} else {
					$entries2Keep[] = $currentRecord;
				}
			}
		} catch (DatabaseObjectNotFoundException $e) {
			// deprecated NC22
			// ignore silently
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

	public function deleteByUserId(string $userId): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where('user_id = :userId')
			->setParameter('userId', $userId);
		$query->execute();
	}

	/**
	 * Delete entries per timestamp
	 */
	public function deleteOldEntries(int $offset): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where(
				$query->expr()->lt('created', $query->createNamedParameter($offset))
			);
		$query->execute();
	}

	/**
	 * Delete processed entries
	 */
	public function deleteProcessedEntries(): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where(
				$query->expr()->gt('processed', $query->createNamedParameter(0))
			);
		$query->execute();
	}
}
