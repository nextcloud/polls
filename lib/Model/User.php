<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
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


namespace OCA\Polls\Model;

use OCP\IUser;
use OCA\Polls\Interfaces\IUserObj;

class User implements \JsonSerializable, IUserObj {
	public const TYPE = 'user';
	public const TYPE_USER = 'user';
	public const TYPE_GROUP = 'group';
	public const TYPE_CONTACTGROUP = 'contactGroup';
	public const TYPE_CONTACT = 'contact';
	public const TYPE_EMAIL = 'email';
	public const TYPE_CIRCLE = 'circle';
	public const TYPE_EXTERNAL = 'external';
	public const TYPE_INVALID = 'invalid';

	/** @var string */
	private $id;

	/** @var IUser */
	private $user;

	/**
	 * User constructor.
	 * @param $type
	 * @param $id
	 * @param $emailAddress
	 * @param $displayName
	 */
	public function __construct(
		$id
	) {
		$this->id = $id;
		$this->load();
	}

	/**
	 * Get userId
	 * @NoAdminRequired
	 * @return String
	 */
	public function getUserId() {
		return $this->id;
	}

	/**
	 * Get userId
	 * @NoAdminRequired
	 * @return String
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * getUser
	 * @NoAdminRequired
	 * @return String
	 */
	public function getUser() {
		return $this->id;
	}

	/**
	 * Get user type
	 * @NoAdminRequired
	 * @return String
	 */
	public function getType() {
		return self::TYPE;
	}

	/**
	 * @NoAdminRequired
	 * @return String
	 */
	public function getLanguage() {
		return \OC::$server->getConfig()->getUserValue($this->id, 'core', 'lang');
	}

	/**
	 * Get displayName
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDisplayName() {
		return \OC::$server->getUserManager()->get($this->id)->getDisplayName();
	}

	/**
	 * @NoAdminRequired
	 * @return String
	 */
	public function getOrganisation() {
		return '';
	}

	/**
	 * Get email address
	 * @NoAdminRequired
	 * @return String
	 */
	public function getEmailAddress() {
		return $this->user->getEMailAddress();
	}

	/**
	 * Get additional description, if available
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDescription() {
		return \OC::$server->getL10N('polls')->t('User');
	}

	/**
	 * Get icon class
	 * @NoAdminRequired
	 * @return String
	 */
	public function getIcon() {
		return 'icon-user';
	}

	/**
	 * Get icon class
	 * @NoAdminRequired
	 * @return String
	 */
	public function getUserIsDisabled() {
		return !\OC::$server->getUserManager()->get($this->id)->isEnabled();
	}

	/**
	 * listRaw
	 * @NoAdminRequired
	 * @param string $query
	 * @return Array
	 */
	public static function listRaw($query = '') {
		return \OC::$server->getUserManager()->search($query);
	}

	/**
	 * search
	 * @NoAdminRequired
	 * @param string $query
	 * @param array $skip - group names to skip in return array
	 * @return Group[]
	 */
	public static function search($query = '', $skip = []) {
		$users = [];
		foreach (self::listRaw($query) as $user) {
			if (!in_array($user->getUID(), $skip)) {
				$users[] = new Self($user->getUID());
			}
		}
		return $users;
	}

	private function load() {
		$this->user = \OC::$server->getUserManager()->get($this->id);
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return	[
			'user'          => $this->id,
			'id'        	=> $this->id,
			'userId'        => $this->id,
			'type'       	=> $this->getType(),
			'displayName'	=> $this->getDisplayName(),
			'organisation'	=> $this->getOrganisation(),
			'emailAddress'	=> $this->getEmailAddress(),
			'desc' 			=> $this->getDescription(),
			'icon'			=> $this->getIcon(),
		];
	}
}
