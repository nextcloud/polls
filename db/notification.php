<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method string getPollId()
 * @method void setPollId(string $value)
 */
class Notification extends Entity {
    public $userId;
    public $pollId;
}
