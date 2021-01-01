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

/**
 * @template-extends QBMapper<Option>
 */
class OptionMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'polls_options', '\OCA\Polls\Db\Option');
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Option
	 */
	public function find(int $id): Option {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
			   $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
		   );

		return $this->findEntity($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Option[]
	 */
	public function findByPoll(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
			   $qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   )
		   ->orderBy('order', 'ASC');

		return $this->findEntities($qb);
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Option
	 */
	public function findByPollAndText($pollId, $pollOptionText): Option {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
			   $qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   )
		   ->andWhere(
			   $qb->expr()->eq('poll_option_text', $qb->createNamedParameter($pollOptionText, IQueryBuilder::PARAM_STR))
		   )
		   ->orderBy('order', 'ASC');

		return $this->findEntity($qb);
	}

	public function remove($optionId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
		   ->where(
			   $qb->expr()->eq('id', $qb->createNamedParameter($optionId, IQueryBuilder::PARAM_INT))
		   );

		$qb->execute();
	}

	public function deleteByPoll($pollId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
		   ->where(
			   $qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   );

		$qb->execute();
	}

	/**
	 * @return void
	 */
	public function removeDuplicates() {
		// remove duplicates from oc_polls_options
		// preserve the first entry
		$query = $this->db->getQueryBuilder();
		$query->select('id', 'poll_id', 'poll_option_text', 'timestamp')
			->from($this->getTableName());
		$foundEntries = $query->execute();

		$delete = $this->db->getQueryBuilder();
		$delete->delete($this->getTableName())
			->where('id = :id');

		$entries2Keep = [];

		while ($row = $foundEntries->fetch()) {
			$currentRecord = [
				$row['poll_id'],
				$row['poll_option_text'],
				$row['timestamp']
			];
			if (in_array($currentRecord, $entries2Keep)) {
				$delete->setParameter('id', $row['id']);
				$delete->execute();
			} else {
				$entries2Keep[] = $currentRecord;
			}
		}
	}
}
