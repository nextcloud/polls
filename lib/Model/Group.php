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

use OCP\IGroupManager;
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
		$this->group = self::getContainer()->query(IGroupManager::class)->get($this->id);
		$this->description = \OC::$server->getL10N('polls')->t('Group');
		try {
			// since NC19
			$this->displayName = $this->group->getDisplayName();
		} catch (\Exception $e) {
			// until NC18
			$this->displayName = $this->id;
		}
	}

	/**
	 * @return User[]
	 */
	public function getMembers(): array {
		$members = [];

		foreach (array_keys(self::getContainer()->query(IGroupManager::class)->displayNamesInGroup($this->id)) as $member) {
			$members[] = new User($member);
		}
		return $members;
	}

	/**
	 * @return Group[]
	 *
	 * @psalm-return list<Group>
	 */
	public static function search(string $query = '', array $skip = []): array {
		$groups = [];

		foreach (self::getContainer()->query(IGroupManager::class)->search($query) as $group) {
			if (!in_array($group->getGID(), $skip)) {
				$groups[] = new Self($group->getGID());
			}
		}
		return $groups;
	}
}
