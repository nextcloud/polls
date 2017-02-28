<?php
namespace OCA\Polls\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method integer getType()
 * @method void setType(integer $value)
 * @method string getTitle()
 * @method void setTitle(string $value)
 * @method string getDescription()
 * @method void setDescription(string $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method timestamp getCreated()
 * @method void setCreated(timestamp $value)
 * @method string getAccess()
 * @method void setAccess(string $value)
 * @method timestamp getExpire()
 * @method void setExpire(timestamp $value)
 * @method string getHash()
 * @method void setHash(string $value)
 * @method integer getIsAnonymous()
 * @method void setIsAnonymous(integer $value)
 * @method integer getFullAnonymous()
 * @method void setFullAnonymous(integer $value)
 */
class Event extends Entity {
    public $type;
    public $title;
    public $description;
    public $owner;
    public $created;
    public $access;
    public $expire;
    public $hash;
    public $isAnonymous;
    public $fullAnonymous;
}
