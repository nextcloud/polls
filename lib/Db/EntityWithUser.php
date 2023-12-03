<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Kai Schröer <git@schroeer.co>
 *
 * @author Kai Schröer <git@schroeer.co>
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

use OCA\Polls\Helper\Container;
use OCP\AppFramework\Db\Entity;
use OCP\IUser;
use OCP\IUserManager;

/**
 * @method int getPollId()
 * @method string getUserId()
 * @method string getDisplayName()
 */

abstract class EntityWithUser extends Entity {
	protected string $publicUserId = '';
	protected ?string $displayName = '';

	public function getIsNoUser(): bool {
		return !(Container::queryClass(IUserManager::class)->get($this->getUserId()) instanceof IUser);
	}

	public function getDisplayName(): ?string {
		if ($this->displayName) {
			return $this->displayName;
		}

		// if (!$this->getUserId()) {
		// 	return 'No UserId';
		// }

		return Container::queryClass(IUserManager::class)->get($this->getUserId())?->getDisplayName() ?? 'Deleted User';
	}

	private function getPublicUserId(): string {
		if (!$this->getUserId()) {
			return '';
		}

		if ($this->publicUserId) {
			return $this->publicUserId;
		}

		return $this->getUserId();
	}

	public function generateHashedUserId(): void {
		$this->publicUserId = hash('md5', $this->getUserId());
	}

	public function getUser(): array {
		return [
			'userId' => $this->getPublicUserId(),
			'displayName' => $this->getDisplayName(),
			'isNoUser' => $this->getIsNoUser(),
		];
	}
}
