<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Model\Group;

use OCA\Polls\Helper\Container;
use OCA\Polls\Model\User\User;
use OCA\Polls\Model\UserBase;
use OCP\IGroupManager;
use OCP\IGroup;

class Group extends UserBase {
	public const TYPE = 'group';
	public const ICON = 'icon-group';

	private IGroup $group;

	public function __construct(
		string $id
	) {
		parent::__construct($id, self::TYPE);
		$this->icon = self::ICON;
		$this->description = $this->l10n->t('Group');
		$this->richObjectType = 'user-group';

		$this->group = Container::queryClass(IGroupManager::class)->get($this->id);
		$this->displayName = $this->group->getDisplayName();
	}

	/**
	 * @return User[]
	 */
	public function getMembers(): array {
		$members = [];

		foreach (array_keys(Container::queryClass(IGroupManager::class)->displayNamesInGroup($this->id)) as $member) {
			$newMember = new User($member);

			if ($newMember->IsEnabled()) {
				$members[] = $newMember;
			}
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

		foreach (Container::queryClass(IGroupManager::class)->search($query) as $group) {
			if (!in_array($group->getGID(), $skip)) {
				$groups[] = new self($group->getGID());
			}
		}
		return $groups;
	}
}
