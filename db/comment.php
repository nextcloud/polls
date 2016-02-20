<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method string getDt()
 * @method void setDt(string $value)
 * @method string getComment()
 * @method void setComment(string $value)
 * @method integer getPollId()
 * @method void setPollId(integer $value)
 */
class Comment extends Entity {
    public $userId;
    public $dt;
    public $comment;
    public $pollId;
}
