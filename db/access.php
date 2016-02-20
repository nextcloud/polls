<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method integer getPId()
 * @method void setPId(integer $value)
 * @method string getAccessType()
 * @method void setAccessType(string $value)
 */
class Access extends Entity {
    public $pId;
    public $accessType;
}
