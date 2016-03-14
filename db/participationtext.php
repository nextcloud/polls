<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method text getText()
 * @method void setText(text $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method integer getPollId()
 * @method void setPollId(integer $value)
 * @method integer getType()
 * @method void setType(integer $value)
 */
class ParticipationText extends Entity {
    public $text;
    public $userId;
    public $pollId;
    public $type;
}
