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

class DateMapper extends Mapper
{

    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'polls_dts', '\OCA\Polls\Db\Date');
    }

    /**
     * @param int $id
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     * @return Date
     */
    public function find($id)
    {
        $sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE id = ?';
        return $this->findEntity($sql, [$id]);
    }

    /**
     * @param string $userId
     * @param string $from
     * @param string $until
     * @param int $limit
     * @param int $offset
     * @return Date[]
     */
    public function findBetween($userId, $from, $until, $limit = null, $offset = null)
    {
        $sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE userId = ? AND timestamp BETWEEN ? AND ?';
        return $this->findEntities($sql, [$userId, $from, $until], $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Date[]
     */
    public function findAll($limit = null, $offset = null)
    {
        $sql = 'SELECT * FROM ' . $this->getTableName();
        return $this->findEntities($sql, [], $limit, $offset);
    }

    /**
     * @param string $pollId
     * @param int $limit
     * @param int $offset
     * @return Date[]
     */
    public function findByPoll($pollId, $limit = null, $offset = null)
    {
        $sql = 'SELECT * FROM ' . $this->getTableName() . ' WHERE poll_id = ?';
        return $this->findEntities($sql, [$pollId], $limit, $offset);
    }

    /**
     * @param string $pollId
     */
    public function deleteByPoll($pollId)
    {
        $sql = 'DELETE FROM ' . $this->getTableName() . ' WHERE poll_id = ?';
        $this->execute($sql, [$pollId]);
    }
}
