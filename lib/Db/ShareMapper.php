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

use OCA\Polls\Exceptions\ShareNotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;

/**
 * @template-extends QBMapper<Share>
 */
class ShareMapper extends QBMapper {
	public const TABLE = Share::TABLE;

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE, Share::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Share[]
	 * @psalm-return array<array-key, Share>
	 */
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName());

		return $this->findEntities($qb);
	}


	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Share[]
	 * @psalm-return array<array-key, Share>
	 */
	public function findByPoll(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   );

		return $this->findEntities($qb);
	}
	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Share[]
	 * @psalm-return array<array-key, Share>
	 */
	public function findByPollUnreminded(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
		   ->andWhere($qb->expr()->eq('reminder_sent', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));

		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 */
	public function findByPollAndUser(int $pollId, string $userId): Share {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   )
		   ->andWhere(
		   	$qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
		   );

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			throw new ShareNotFoundException("Share not found by userId and pollId", $userId, $pollId);
		}
	}

	public function findByToken(string $token): Share {
		$userId = '';
		$pollId = 0;

		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		->from($this->getTableName())
		->where(
			$qb->expr()->eq('token', $qb->createNamedParameter($token, IQueryBuilder::PARAM_STR))
		);
		
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			$exploded = explode('_', $token);
			// Check, if token is a fake token
			if ($exploded[1] === 'deleted' && $exploded[1] === 'share') {
				$userId = $exploded[2];
				$pollId = intval($exploded[3]);
			}
			throw new ShareNotFoundException('Token ' . $token . ' does not exist', $userId, $pollId, $token);
		}
	}

	public function deleteByPoll(int $pollId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   );

		$qb->executeStatement();
	}

	public function deleteByUserId(string $userId): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where('user_id = :userId')
			->setParameter('userId', $userId);
		$query->executeStatement();
	}

	/**
	 * @return void
	 */
	public function deleteByIdAndType(string $id, string $type): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where('user_id = :id')
			->andWhere('type = :type')
			->setParameter('id', $id)
			->setParameter('type', $type);
		$query->executeStatement();
	}

	/**
	 * @return void
	 */
	public function remove(int $shareId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
		   ->where(
		   	$qb->expr()->eq('id', $qb->createNamedParameter($shareId, IQueryBuilder::PARAM_INT))
		   );

		$qb->executeStatement();
	}

	public function removeDuplicates(?IOutput $output = null): int {
		$count = 0;
		try {
			$query = $this->db->getQueryBuilder();
			// make sure, all public shares fit to the unique index added in schemaChange(),
			// by copying token to user_id
			$query->update($this->getTableName())
				->set('user_id', 'token')
				->where('type = :type')
				->setParameter('type', 'public')
				->executeStatement();

			// remove duplicates from polls_share
			// preserve the first entry
			$query = $this->db->getQueryBuilder();
			$query->select('id', 'type', 'poll_id', 'user_id')
				->from($this->getTableName());
			$foundEntries = $query->executeQuery();

			$delete = $this->db->getQueryBuilder();
			$delete->delete($this->getTableName())->where('id = :id');

			$entries2Keep = [];

			while ($row = $foundEntries->fetch()) {
				$currentRecord = [
					$row['poll_id'],
					$row['type'],
					$row['user_id']
				];

				if (in_array($currentRecord, $entries2Keep) || $row['user_id'] === null || $row['type'] === '') {
					$delete->setParameter('id', $row['id']);
					$delete->executeStatement();
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
}
