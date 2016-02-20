<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDb;

class NotificationMapper extends Mapper {

    public function __construct(IDB $db) {
        parent::__construct($db, 'polls_notif', '\OCA\Polls\Db\Notification');
    }

    /**
     * @param int $id
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     * @return Notification
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*polls_notif` '.
            'WHERE `id` = ?';
        return $this->findEntity($sql, [$id]);
    }

    /**
     * @param string $userId
     * @param string $from
     * @param string $until
     * @param int $limit
     * @param int $offset
     * @return Notification[]
     */
    public function findBetween($userId, $from, $until, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_notif` '.
            'WHERE `userId` = ?'.
            'AND `timestamp` BETWEEN ? and ?';
        return $this->findEntities($sql, [$userId, $from, $until], $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Notification[]
     */
    public function findAll($limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_notif`';
        return $this->findEntities($sql, $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Notification[]
     */
    public function findAllByPoll($pollId, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_notif` WHERE `poll_id`=?';
        return $this->findEntities($sql, [$pollId], $limit, $offset);
    }

    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @return Notification
     */
    public function findByUserAndPoll($pollId, $userId) {
        $sql = 'SELECT * FROM `*PREFIX*polls_notif` WHERE `poll_id`=? AND `user_id`=?';
        return $this->findEntity($sql, [$pollId, $userId]);
    }
}
