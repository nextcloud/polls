<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method timestamp getDt()
 * @method void setDt(timestamp $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method integer getPollId()
 * @method void setPollId(integer $value)
 * @method integer getType()
 * @method void setType(integer $value)
 */
class Participation extends Entity {
    public $dt;
    public $userId;
    public $pollId;
    public $type;
}
