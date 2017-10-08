<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

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
class Event extends Entity
{
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
