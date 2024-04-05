<?php

declare(strict_types=1);
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
use OCP\IGroup;
use OCP\IGroupManager;

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

		$this->setUp();
	}

	private function setUp(): void {
		$this->group = $this->groupManager->get($this->id);
		$this->displayName = $this->group->getDisplayName();
	}

	/**
	 * @return User[]
	 */
	public function getMembers(): array {
		$members = [];
		$usersIdsInGroup = array_keys($this->groupManager->displayNamesInGroup($this->id));

		foreach ($usersIdsInGroup as $userId) {
			$newMember = new User($userId);

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
