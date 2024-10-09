<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\Group;

use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\User\User;
use OCA\Polls\Model\UserBase;
use OCP\IGroup;
use OCP\IGroupManager;

class Group extends UserBase {
	public const TYPE = 'group';

	private IGroup $group;

	public function __construct(
		string $id,
	) {
		parent::__construct($id, self::TYPE);
		$this->description = $this->l10n->t('Group');
		$this->richObjectType = 'user-group';

		$this->setUp();
	}

	private function setUp(): void {
		$foundGroup = $this->groupManager->get($this->id);
		if ($foundGroup === null) {
			throw new NotFoundException('Group not found');
		}

		$this->group = $foundGroup;
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
