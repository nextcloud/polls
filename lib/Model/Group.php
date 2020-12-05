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

class Group extends UserGroupClass {
	public const TYPE = 'group';
	public const ICON = 'icon-group';

	/** @var IGroup */
	private $group;

	public function __construct(
		$id
	) {
		parent::__construct($id, self::TYPE);
		$this->icon = self::ICON;

		$this->group = \OC::$server->getGroupManager()->get($this->id);
		$this->description = \OC::$server->getL10N('polls')->t('Group');
		try {
			// since NC19
			$this->displayName = $this->group->getDisplayName();
		} catch (\Exception $e) {
			// until NC18
			$this->displayName = $this->id;
		}
	}

	public static function listRaw(string $query = '') {
		return \OC::$server->getGroupManager()->search($query);
	}

	/**
	 * @return array
	 *
	 * @psalm-return list<mixed>
	 */
	public static function search(string $query = '', array $skip = []) {
		$groups = [];
		foreach (self::listRaw($query) as $group) {
			if (!in_array($group->getGID(), $skip)) {
				$groups[] = new Self($group->getGID());
			}
		}
		return $groups;
	}

	/**
	 * @return User[]
	 *
	 * @psalm-return non-empty-list<User>
	 */
	public function getMembers() {
		$members = [];
		foreach (array_keys(\OC::$server->getGroupManager()->displayNamesInGroup($this->id)) as $member) {
			$members[] = new User($member);
		}
		return $members;
	}
}
