<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDb;

class DateMapper extends Mapper {

    public function __construct(IDB $db) {
        parent::__construct($db, 'polls_dts', '\OCA\Polls\Db\Date');
    }

    /**
     * @param int $id
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     * @return Date
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*polls_dts` '.
            'WHERE `id` = ?';
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
    public function findBetween($userId, $from, $until, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_dts` '.
            'WHERE `userId` = ?'.
            'AND `timestamp` BETWEEN ? and ?';
        return $this->findEntities($sql, [$userId, $from, $until], $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Date[]
     */
    public function findAll($limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_dts`';
        return $this->findEntities($sql, $limit, $offset);
    }

    /**
     * @param string $pollId
     * @param int $limit
     * @param int $offset
     * @return Date[]
     */
    public function findByPoll($pollId, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_dts` WHERE poll_id=?';
        return $this->findEntities($sql, [$pollId], $limit, $offset);
    }

    /**
     * @param string $pollId
     */
    public function deleteByPoll($pollId) {
        $sql = 'DELETE FROM `*PREFIX*polls_dts` WHERE poll_id=?';
        $this->execute($sql, [$pollId]);
    }
}
