<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 * @author Kai Schröer <git@schroeer.co>
 * @author René Gieling <github@dartcafe.de>
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

use JsonSerializable;

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
 * @method string getCreated()
 * @method void setCreated(string $value)
 * @method string getAccess()
 * @method void setAccess(string $value)
 * @method string getExpire()
 * @method void setExpire(string $value)
 * @method integer getIsAnonymous()
 * @method void setIsAnonymous(integer $value)
 * @method integer getFullAnonymous()
 * @method void setFullAnonymous(integer $value)
 * @method integer getAllowMaybe()
 * @method void setAllowMaybe(integer $value)
 */
class Event extends Entity implements JsonSerializable {
	protected $type;
	protected $title;
	protected $description;
	protected $owner;
	protected $created;
	protected $access;
	protected $hash;
	protected $expire;
	protected $isAnonymous;
	protected $fullAnonymous;
	protected $allowMaybe;

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'type' => $this->type,
			'title' => $this->title,
			'description' => $this->description,
			'owner' => $this->owner,
			'created' => $this->created,
			'access' => $this->access,
			'expire' => $this->expire,
			'isAnonymous' => $this->isAnonymous,
			'fullAnonymous' => $this->fullAnonymous,
			'allowMaybe' => $this->allowMaybe
		];
	}
}
