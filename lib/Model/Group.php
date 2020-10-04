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

use OCP\IGroup;
use OCA\Polls\Interfaces\IUserObj;

class Group implements \JsonSerializable, IUserObj {
	public const TYPE = 'group';

	/** @var string */
	private $id;

	/** @var IGroup */
	private $group;

	/**
	 * Group constructor.
	 * @param $id
	 * @param $displayName
	 */
	public function __construct(
		$id
	) {
		$this->id = $id;
		$this->load();
	}

	/**
	 * getId
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
	 * getType
	 * @NoAdminRequired
	 * @return String
	 */
	public function getType() {
		return self::TYPE;
	}

	/**
	 * getlanguage
	 * @NoAdminRequired
	 * @return String
	 */
	public function getLanguage() {
		return '';
	}

	/**
	 * getDisplayName
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDisplayName() {
		try {
			// since NC19
			return $this->group->getDisplayName();
		} catch (\Exception $e) {
			// until NC18
			return $this->id;
		}
	}

	/**
	 * getOrganisation
	 * @NoAdminRequired
	 * @return String
	 */
	public function getOrganisation() {
		return '';
	}

	/**
	 * getEmailAddress
	 * @NoAdminRequired
	 * @return String
	 */
	public function getEmailAddress() {
		return '';
	}

	/**
	 * getDescription
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDescription() {
		return \OC::$server->getL10N('polls')->t('Group');
	}

	/**
	 * getIcon
	 * @NoAdminRequired
	 * @return String
	 */
	public function getIcon() {
		return 'icon-group';
	}

	private function load() {
		$this->group = \OC::$server->getGroupManager()->get($this->id);
	}

	/**
	 * listRaw
	 * @NoAdminRequired
	 * @param string $query
	 * @return Array
	 */
	public static function listRaw($query = '') {
		return \OC::$server->getGroupManager()->search($query);
	}

	/**
	 * search
	 * @NoAdminRequired
	 * @param string $query
	 * @param array $skip - group names to skip in return array
	 * @return Group[]
	 */
	public static function search($query = '', $skip = []) {
		$groups = [];
		foreach (self::listRaw($query) as $group) {
			if (!in_array($group->getGID(), $skip)) {
				$groups[] = new Self($group->getGID());
			}
		}
		return $groups;
	}

	/**
	 * Get a list of circle members
	 * @NoAdminRequired
	 * @param string $query
	 * @return User[]
	 */
	public function getMembers() {
		$members = [];
		foreach (array_keys(\OC::$server->getGroupManager()->displayNamesInGroup($this->id)) as $member) {
			$members[] = new user($member);
		}
		return $members;
	}



	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return	[
			'id'        	=> $this->id,
			'user'          => $this->id,
			'type'       	=> $this->getType(),
			'displayName'	=> $this->getDisplayName(),
			'organisation'	=> $this->getOrganisation(),
			'emailAddress'	=> $this->getEmailAddress(),
			'desc' 			=> $this->getDescription(),
			'icon'			=> $this->getIcon(),
			'isNoUser'		=> true,
		];
	}
}
