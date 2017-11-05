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

/**
 * @method integer getType()
 * @method void setType(integer $value)
 * @method string getTitle()
 * @method void setTitle(string $value)
 * @method string getDescription()
 * @method void setDescription(string $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method string getCreated()
 * @method void setCreated(string $value)
 * @method string getAccess()
 * @method void setAccess(string $value)
 * @method string getExpire()
 * @method void setExpire(string $value)
 * @method string getHash()
 * @method void setHash(string $value)
 * @method boolean getIsAnonymous()
 * @method void setIsAnonymous(boolean $value)
 * @method boolean getFullAnonymous()
 * @method void setFullAnonymous(boolean $value)
 */
class Event extends Model {
	protected $type;
	protected $title;
	protected $description;
	protected $owner;
	protected $created;
	protected $access;
	protected $expire;
	protected $hash;
	protected $isAnonymous;
	protected $fullAnonymous;

	/**
	 * Event constructor.
	 */
	public function __construct() {
		$this->addType('isAnonymous', 'boolean');
		$this->addType('fullAnonymous', 'boolean');
	}
}
