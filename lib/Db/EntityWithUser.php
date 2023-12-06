<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Db;

use OCA\Polls\Helper\Container;
use OCA\Polls\Model\User\User;
use OCP\AppFramework\Db\Entity;
use OCP\IUser;
use OCP\IUserManager;

/**
 * @method int getPollId()
 * @method string getUserId()
 * @method string getDisplayName()
 * @method string getEmailAdress()
 * @method string getUserType()
 */

abstract class EntityWithUser extends Entity {
	protected string $publicUserId = '';
	protected ?string $displayName = '';
	protected ?string $emailAddress = '';
	protected ?string $userType = '';

	public function getIsNoUser(): bool {
		return !(Container::queryClass(IUserManager::class)->get($this->getUserId()) instanceof IUser);
	}

	/**
	 * Returns the displayName
	 *
	 * - first tries to get displayname from internal user
	 * - then try to get it from joined share
	 * - otherwise assume a deleted user
	 **/
	public function getDisplayName(): ?string {
		return Container::queryClass(IUserManager::class)->get($this->getUserId())?->getDisplayName()
			?? $this->displayName
			?? 'Deleted User';
	}

	/**
	 * Returns user type
	 *
	 * - first tries to get type from joined share
	 * - then try to verify an internal user and set type user
	 * - otherwise assume a deleted user
	 **/
	public function getUserType(): ?string {
		return $this->userType
			?? Container::queryClass(IUserManager::class)->get($this->getUserId())
				? User::TYPE_USER
				: User::TYPE_GHOST;
	}

	/**
	 * Returns email address
	 *
	 * - first tries to get emeil address from internal user
	 * - then get it from joined share
	 **/
	public function getEmailAddress(): ?string {
		return Container::queryClass(IUserManager::class)->get($this->getUserId())?->getEmailAddress()
			?? $this->emailAddress;
	}

	/**
	 * Returns an obfuscated userId
	 *
	 * Avoids leaking internal userIds by replacing the actual userId by another string in public access
	 **/
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
			'emailAddress' => $this->getEmailAddress(),
			'isNoUser' => $this->getIsNoUser(),
			'type' => $this->getUserType(),
		];
	}
}
