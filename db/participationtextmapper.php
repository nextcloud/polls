<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Mapper;
use OCP\IDb;

class ParticipationTextMapper extends Mapper {

    public function __construct(IDB $db) {
        parent::__construct($db, 'polls_particip_text', '\OCA\Polls\Db\ParticipationText');
    }

    /**
     * @param int $id
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     * @return ParticipationText
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*polls_particip_text` '.
            'WHERE `id` = ?';
        return $this->findEntity($sql, [$id]);
    }

    public function deleteByPollAndUser($pollId, $userId) {
        $sql = 'DELETE FROM `*PREFIX*polls_particip_text` WHERE poll_id=? AND user_id=?';
        $this->execute($sql, [$pollId, $userId]);
    }

    /**
     * @param string $userId
     * @param string $from
     * @param string $until
     * @param int $limit
     * @param int $offset
     * @return ParticipationText[]
     */
    public function findBetween($userId, $from, $until, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_particip_text` '.
            'WHERE `userId` = ?'.
            'AND `timestamp` BETWEEN ? and ?';
        return $this->findEntities($sql, [$userId, $from, $until], $limit, $offset);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return ParticipationText[]
     */
    public function findAll($limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_particip_text`';
        return $this->findEntities($sql, [], $limit, $offset);
    }

    /**
     * @param string $userId
     * @param int $limit
     * @param int $offset
     * @return ParticipationText[]
     */
    public function findDistinctByUser($userId, $limit=null, $offset=null) {
        $sql = 'SELECT DISTINCT * FROM `*PREFIX*polls_particip_text` WHERE user_id=?';
        return $this->findEntities($sql, [$userId], $limit, $offset);
    }

    /**
     * @param string $userId
     * @param int $limit
     * @param int $offset
     * @return ParticipationText[]
     */
    public function findByPoll($pollId, $limit=null, $offset=null) {
        $sql = 'SELECT * FROM `*PREFIX*polls_particip_text` WHERE poll_id=?';
        return $this->findEntities($sql, [$pollId], $limit, $offset);
    }

    /**
     * @param string $pollId
     */
    public function deleteByPoll($pollId) {
        $sql = 'DELETE FROM `*PREFIX*polls_particip_text` WHERE poll_id=?';
        $this->execute($sql, [$pollId], $limit, $offset);
    }
}
