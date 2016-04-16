<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDb;

class CommentMapper extends Mapper {

    public function __construct(IDB $db) {
        parent::__construct($db, 'polls_comments', '\OCA\Polls\Db\Comment');
    }

    /**
     * @param int $id
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     * @return Comment
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*polls_comments` '.
            'WHERE `id` = ?';
        return $this->findEntity($sql, [$id]);
    }

    /**
     * @param string $userId
     * @param string $from
     * @param string $until
     * @param int $limit
     * @param int $offset
     * @return Comment[]
     */
    public function findBetween($userId, $from, $until, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_comments` '.
            'WHERE `userId` = ?'.
            'AND `timestamp` BETWEEN ? and ?';
        return $this->findEntities($sql, [$userId, $from, $until], $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return Comment[]
     */
    public function findAll($limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_comments`';
        return $this->findEntities($sql, [], $limit, $offset);
    }

    /**
     * @param string $userId
     * @param int $limit
     * @param int $offset
     * @return Comment[]
     */
    public function findDistinctByUser($userId, $limit=null, $offset=null) {
        $sql = 'SELECT DISTINCT * FROM `*PREFIX*polls_comments` WHERE user_id=?';
        return $this->findEntities($sql, [$userId], $limit, $offset);
    }

    /**
     * @param string $userId
     * @param int $limit
     * @param int $offset
     * @return Comment[]
     */
    public function findByPoll($pollId, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_comments` WHERE poll_id=? ORDER BY Dt DESC';
        return $this->findEntities($sql, [$pollId], $limit, $offset);
    }

    /**
     * @param string $pollId
     */
    public function deleteByPoll($pollId) {
        $sql = 'DELETE FROM `*PREFIX*polls_comments` WHERE poll_id=?';
        $this->execute($sql, [$pollId], $limit, $offset);
    }
}
