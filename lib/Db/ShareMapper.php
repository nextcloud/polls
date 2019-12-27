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

class ShareMapper extends QBMapper {

	/**
	 * CommentMapper constructor.
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'polls_share', '\OCA\Polls\Db\Share');
	}

	/**
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return array
	 */
	public function findAll() {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName());

		return $this->findEntities($qb);
	}


	/**
	 * @param int $pollId
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return array
	 */

	public function findByPoll($pollId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
			   $qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   );

		return $this->findEntities($qb);
	}

	/**
	 * @param string $token
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @return Share
	 */

	public function findByToken($token) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
		   ->from($this->getTableName())
		   ->where(
			   $qb->expr()->eq('token', $qb->createNamedParameter($token, IQueryBuilder::PARAM_STR))
		   );

		return $this->findEntity($qb);
	}

	/**
	 * @param int $pollId
	 */
	public function deleteByPoll($pollId) {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
		   ->where(
			   $qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
		   );

	   $qb->execute();
	}

	/**
	 * @param int $shareId
	 */
	public function remove($shareId) {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
		   ->where(
			   $qb->expr()->eq('id', $qb->createNamedParameter($shareId, IQueryBuilder::PARAM_INT))
		   );

	   $qb->execute();
	}

}
