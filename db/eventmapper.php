<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDb;

class EventMapper extends Mapper {

    public function __construct(IDB $db) {
        parent::__construct($db, 'polls_events', '\OCA\Polls\Db\Event');
    }

    /**
     * @param int $id
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     * @return Event
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*polls_events` '.
            'WHERE `id` = ?';
        return $this->findEntity($sql, [$id]);
    }

    /**
     * @param string $userId
     * @param string $from
     * @param string $until
     * @param int $limit
     * @param int $offset
     * @return Event[]
     */
    public function findBetween($userId, $from, $until, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_events` '.
            'WHERE `userId` = ?'.
            'AND `timestamp` BETWEEN ? and ?';
        return $this->findEntities($sql, [$userId, $from, $until], $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Event[]
     */
    public function findAll($limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_events`';
        return $this->findEntities($sql, $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Event
     */
    public function findByHash($hash, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_events` WHERE `hash`=?';
        return $this->findEntity($sql, [$hash], $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Event[]
     */
    public function findAllForUser($userId, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_events` WHERE `owner`=?';
        return $this->findEntities($sql, [$userId], $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Event[]
     */
    public function findAllForUserWithInfo($userId, $limit=null, $offset=null) {
        $sql = 'SELECT DISTINCT *PREFIX*polls_events.id,
                                *PREFIX*polls_events.hash,
                                *PREFIX*polls_events.type,
                                *PREFIX*polls_events.title,
                                *PREFIX*polls_events.description,
                                *PREFIX*polls_events.owner,
                                *PREFIX*polls_events.created,
                                *PREFIX*polls_events.access,
                                *PREFIX*polls_events.expire
                FROM *PREFIX*polls_events
                LEFT JOIN *PREFIX*polls_particip
                    ON *PREFIX*polls_events.id = *PREFIX*polls_particip.id
                LEFT JOIN *PREFIX*polls_comments
                    ON *PREFIX*polls_events.id = *PREFIX*polls_comments.id
                WHERE
                    (*PREFIX*polls_events.access =? and *PREFIX*polls_events.owner =?)
                    OR
                    *PREFIX*polls_events.access !=?
                    OR
                    *PREFIX*polls_particip.user_id =?
                    OR
                    *PREFIX*polls_comments.user_id =?
                    ORDER BY created';
        return $this->findEntities($sql, ['hidden', $userId, 'hidden', $userId, $userId], $limit, $offset);
    }
}
