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

/**
 * @template-extends QBMapper<Preferences>
 */
class PreferencesMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'polls_preferences', Preferences::class);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 * @return Preferences
	 */
	public function find(string $userId): Preferences {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
			   $qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
		   );

		return $this->findEntity($qb);
	}

	public function removeDuplicates($output = null): int {
		$count = 0;
		try {
			$query = $this->db->getQueryBuilder();
			$query->delete($this->getTableName())
				->where('user_id = :userId')
				->setParameter('userId', '');
			$query->execute();

			// remove duplicate preferences from oc_polls_preferences
			// preserve the last user setting in the db
			$query = $this->db->getQueryBuilder();
			$query->select('id', 'user_id')
				->from($this->getTableName());
			$users = $query->execute();

			$delete = $this->db->getQueryBuilder();
			$delete->delete($this->getTableName())
				->where('id = :id');

			$userskeep = [];

			while ($row = $users->fetch()) {
				if (in_array($row['user_id'], $userskeep)) {
					$delete->setParameter('id', $row['id']);
					$delete->execute();
					$count++;
				} else {
					$userskeep[] = $row['user_id'];
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

	/**
	 * @return void
	 */
	public function deleteByUserId(string $userId): void {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->getTableName())
			->where('user_id = :userId')
			->setParameter('userId', $userId);
		$query->execute();
	}
}
