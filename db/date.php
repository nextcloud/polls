<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method timestamp getDt()
 * @method void setDt(timestamp $value)
 * @method integer getPollId()
 * @method void setPollId(integer $value
 */
class Date extends Entity {
    public $dt;
    public $pollId;
}
