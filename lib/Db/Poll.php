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

use OCP\IUser;
use OCP\AppFramework\Db\Entity;
use OCA\Polls\Model\User;

/**
 * @method string getType()
 * @method void setType(string $value)
 * @method string getTitle()
 * @method void setTitle(string $value)
 * @method string getDescription()
 * @method void setDescription(string $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method int getCreated()
 * @method void setCreated(integer $value)
 * @method int getExpire()
 * @method void setExpire(integer $value)
 * @method int getDeleted()
 * @method void setDeleted(integer $value)
 * @method string getAccess()
 * @method void setAccess(string $value)
 * @method int getAnonymous()
 * @method void setAnonymous(integer $value)
 * @method int getFullAnonymous()
 * @method void setFullAnonymous(integer $value)
 * @method int getAllowMaybe()
 * @method void setAllowMaybe(integer $value)
 * @method string getOptions()
 * @method void setOptions(string $value)
 * @method string getSettings()
 * @method void setSettings(string $value)
 * @method int getVoteLimit()
 * @method void setVoteLimit(integer $value)
 * @method string getShowResults()
 * @method void setShowResults(string $value)
 * @method int getAdminAccess()
 * @method void setAdminAccess(integer $value)
 * @method int getImportant()
 * @method void setImportant(integer $value)
 */
class Poll extends Entity implements JsonSerializable {
	public const TYPE_DATE = 'datePoll';
	public const TYPE_TEXT = 'textPoll';
	public const ACCESS_HIDDEN = 'hidden';
	public const ACCESS_PUBLIC = 'public';
	public const SHOW_RESULTS_ALWAYS = 'always';
	public const SHOW_RESULTS_CLOSED = 'closed';
	public const SHOW_RESULTS_NEVER = 'never';

	/** @var string $type */
	protected $type;

	/** @var string $title */
	protected $title;

	/** @var string $description */
	protected $description;

	/** @var string $owner */
	protected $owner;

	/** @var int $created */
	protected $created;

	/** @var int $expire */
	protected $expire;

	/** @var int $deleted */
	protected $deleted;

	/** @var string $access */
	protected $access;

	/** @var int $anonymous */
	protected $anonymous;

	/** @var int $fullAnonymous */
	protected $fullAnonymous;

	/** @var int $allowMaybe */
	protected $allowMaybe;

	/** @var string $options */
	protected $options;

	/** @var string $settings*/
	protected $settings;

	/** @var int $voteLimit*/
	protected $voteLimit;

	/** @var string $showResults */
	protected $showResults;

	/** @var int $adminAccess*/
	protected $adminAccess;

	/** @var int $important*/
	protected $important;

	public function jsonSerialize() {
		return [
			'id' => intval($this->id),
			'type' => $this->type,
			'title' => $this->title,
			'description' => $this->description,
			'owner' => $this->owner,
			'created' => intval($this->created),
			'expire' => intval($this->expire),
			'deleted' => intval($this->deleted),
			'access' => $this->access,
			'anonymous' => intval($this->anonymous),
			'allowMaybe' => intval($this->allowMaybe),
			'settings' => $this->settings,
			'voteLimit' => intval($this->voteLimit),
			'showResults' => $this->showResults === 'expired' ? Poll::SHOW_RESULTS_CLOSED : $this->showResults,
			'adminAccess' => intVal($this->adminAccess),
			'ownerDisplayName' => $this->getDisplayName(),
			'important' => intVal($this->important)
		];
	}

	public function deserializeArray($array) {
		$this->setTitle(isset($array['title']) ? $array['title'] : $this->getTitle());
		$this->setDescription(isset($array['description']) ? $array['description'] : $this->getDescription());
		$this->setAccess(isset($array['access']) ? $array['access'] : $this->getAccess());
		$this->setExpire(isset($array['expire']) ? $array['expire'] : $this->getExpire());
		$this->setAnonymous(isset($array['anonymous']) ? +$array['anonymous'] : $this->getAnonymous());
		$this->setAllowMaybe(isset($array['allowMaybe']) ? +$array['allowMaybe'] : $this->getAllowMaybe());
		$this->setVoteLimit(isset($array['voteLimit']) ? $array['voteLimit'] : $this->getVoteLimit());
		$this->setShowResults(isset($array['showResults']) ? $array['showResults'] : $this->getShowResults());
		$this->setDeleted(isset($array['deleted']) ? $array['deleted'] : $this->getDeleted());
		$this->setAdminAccess(isset($array['adminAccess']) ? +$array['adminAccess'] : $this->getAdminAccess());
		$this->setImportant(isset($array['important']) ? +$array['important'] : $this->getImportant());
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getExpired(): bool {
		return (
			   $this->getExpire() > 0
			&& $this->getExpire() < time()
		);
	}

	/**
	 * @return string
	 */
	private function getDisplayName() {
		if (\OC::$server->getUserManager()->get($this->owner) instanceof IUser) {
			return \OC::$server->getUserManager()->get($this->owner)->getDisplayName();
		} else {
			return $this->owner;
		}
	}
	/**
	 * @return User
	 */
	public function getOwnerUserObject() {
		return new User($this->owner);
	}
}
