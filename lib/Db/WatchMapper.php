<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
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

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

/**
 * @template-extends QBMapper<Watch>
 */
class WatchMapper extends QBMapper {
	public const TABLE = Watch::TABLE;

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE, Watch::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Watch[]
	 */
	public function findUpdatesForPollId(int $pollId, int $offset): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where($qb->expr()->gt('updated', $qb->createNamedParameter($offset)))
		   ->andWhere($qb->expr()->orX(
			   $qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId)),
			   $qb->expr()->eq('table', $qb->createNamedParameter('polls'))
		   ));

		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Watch
	 */
	public function findForPollIdAndTable(int $pollId, string $table): Watch {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
			   $qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId))
		   )
		   ->andWhere(
			   $qb->expr()->eq('table', $qb->createNamedParameter($table))
		   );

		return $this->findEntity($qb);
	}

	/**
	 * Delete entries per timestamp
	 * @return void
	 */
	public function deleteOldEntries(int $offset): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where(
				$query->expr()->lt('updated', $query->createNamedParameter($offset))
			);
		$query->executeStatement();
	}
}
