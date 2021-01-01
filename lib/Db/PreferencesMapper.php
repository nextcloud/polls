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

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;
use Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * @template-extends QBMapper<Preferences>
 */
class PreferencesMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'polls_preferences', '\OCA\Polls\Db\Preferences');
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 * @return Preferences
	 */

	public function find($userId): Preferences {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
			   $qb->expr()->eq('user_id', $qb->createNamedParameter($userId))
		   );

		return $this->findEntity($qb);
	}

	/**
	 * @return void
	 */
	public function removeDuplicates() {
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
				} else {
					$userskeep[] = $row['user_id'];
				}
			}
		} catch (TableNotFoundException $e) {
			// ignore
		}
	}
}
