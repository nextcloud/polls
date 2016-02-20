<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getText()
 * @method void setText(string $value)
 * @method integer getPollId()
 * @method void setPollId(integer $value
 */
class Text extends Entity {
    public $text;
    public $pollId;
}
