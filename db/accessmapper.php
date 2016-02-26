<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDb;

class AccessMapper extends Mapper {

    public function __construct(IDB $db) {
        parent::__construct($db, 'polls_access', '\OCA\Polls\Db\Access');
    }

    /**
     * @param int $id
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     * @return Favorite
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*polls_access` '.
            'WHERE `id` = ?';
        return $this->findEntity($sql, [$id]);
    }

    /**
     * @param string $userId
     * @param string $from
     * @param string $until
     * @param int $limit
     * @param int $offset
     * @return Favorite[]
     */
    public function findBetween($userId, $from, $until, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_access` '.
            'WHERE `userId` = ?'.
            'AND `timestamp` BETWEEN ? and ?';
        return $this->findEntities($sql, [$userId, $from, $until], $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Favorite[]
     */
    public function findAll($limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_access`';
        return $this->findEntities($sql, [], $limit, $offset);
    }
}
