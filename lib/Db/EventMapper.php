<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;

class EventMapper extends Mapper {

	/**
	 * EventMapper constructor.
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'polls_events', '\OCA\Polls\Db\Event');
	}

	/**
	 * @param int $id
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 * @return Event
	 */
	public function find($id) {
		$sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE id = ?';
		return $this->findEntity($sql, [$id]);
	}

	/**
	 * @param string $hash
	 * @param int $limit
	 * @param int $offset
	 * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
	 * @return Event
	 */
	public function findByHash($hash, $limit = null, $offset = null) {
		$sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE hash = ?';
		return $this->findEntity($sql, [$hash], $limit, $offset);
	}

	/**
	 * @param string $userId
	 * @param int $limit
	 * @param int $offset
	 * @return Event[]
	 */
	public function findAllForUser($userId, $limit = null, $offset = null) {
		$sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE owner = ?';
		return $this->findEntities($sql, [$userId], $limit, $offset);
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return Event[]
	 */
	public function findAll($limit = null, $offset = null) {
		$sql = 'SELECT * FROM ' . $this->getTableName();
		return $this->findEntities($sql, ['*'], $limit, $offset);
	}

	/**
	 * @param string $userId
	 * @param int $limit
	 * @param int $offset
	 * @return Event[]
	 */
	public function findAllForUserWithInfo($userId, $limit = null, $offset = null) {
		$sql = 'SELECT DISTINCT *PREFIX*polls_events.id,
								*PREFIX*polls_events.hash,
								*PREFIX*polls_events.type,
								*PREFIX*polls_events.title,
								*PREFIX*polls_events.description,
								*PREFIX*polls_events.owner,
								*PREFIX*polls_events.created,
								*PREFIX*polls_events.access,
								*PREFIX*polls_events.expire,
								*PREFIX*polls_events.is_anonymous,
								*PREFIX*polls_events.full_anonymous
				FROM *PREFIX*polls_events
				LEFT JOIN *PREFIX*polls_votes
					ON *PREFIX*polls_events.id = *PREFIX*polls_votes.id
				LEFT JOIN *PREFIX*polls_comments
					ON *PREFIX*polls_events.id = *PREFIX*polls_comments.id
				WHERE
					(*PREFIX*polls_events.access = ? AND *PREFIX*polls_events.owner = ?)
					OR
					*PREFIX*polls_events.access != ?
					OR
					*PREFIX*polls_votes.user_id = ?
					OR
					*PREFIX*polls_comments.user_id = ?
					ORDER BY created';
		return $this->findEntities($sql, ['hidden', $userId, 'hidden', $userId, $userId], $limit, $offset);
	}
}
