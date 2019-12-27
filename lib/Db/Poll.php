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
 * @method void setType(string $value)
 * @method string getTitle()
 * @method void setTitle(string $value)
 * @method string getDescription()
 * @method void setDescription(string $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method string getCreated()
 * @method void setCreated(integer $value)
 * @method string getExpire()
 * @method void setExpire(integer $value)
 * @method integer getDeleted()
 * @method void setDeleted(integer $value)
 * @method string getAccess()
 * @method void setAccess(string $value)
 * @method integer getAnonymous()
 * @method void setAnonymous(integer $value)
 * @method integer getFullAnonymous()
 * @method void setFullAnonymous(integer $value)
 * @method integer getAllowMaybe()
 * @method void setAllowMaybe(integer $value)
 * @method integer getOptions()
 * @method void setOptions(string $value)
 * @method integer getSettings()
 * @method void setSettings(string $value)
 * @method integer getVoteLimit()
 * @method void setVoteLimit(integer $value)
 * @method integer getShowResults()
 * @method void setShowResults(integer $value)
 */
class Poll extends Entity implements JsonSerializable {
	protected $type;
	protected $title;
	protected $description;
	protected $owner;
	protected $created;
	protected $expire;
	protected $deleted;
	protected $access;
	protected $Anonymous;
	protected $fullAnonymous;
	protected $allowMaybe;
	protected $options;
	protected $settings;
	protected $voteLimit;
	protected $showResults;

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'type' => $this->type,
			'title' => $this->title,
			'description' => $this->description,
			'owner' => $this->owner,
			'created' => $this->created,
			'expire' => $this->expire,
			'deleted' => $this->deleted,
			'access' => $this->access,
			'Anonymous' => $this->Anonymous,
			'fullAnonymous' => $this->fullAnonymous,
			'allowMaybe' => $this->allowMaybe,
			'options' => $this->options,
			'settings' => $this->settings,
			'voteLimit' => $this->voteLimit,
			'showResults' => $this->showResults
		];
	}
}
