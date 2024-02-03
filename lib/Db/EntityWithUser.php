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
use OCP\AppFramework\Db\Entity;

/**
 * @method int getPollId()
 * @method ?string getUserId()
 * @method ?string getDisplayName()
 * @method ?string getEmailAdress()
 */

abstract class EntityWithUser extends Entity {
	// protected ?string $displayName = '';
	// protected ?string $emailAddress = '';

	/**
	 * Returns the displayName
	 *
	 * - first tries to get displayname from internal user
	 * - then try to get it from joined share
	 * - otherwise assume a deleted user
	 **/
	// public function getDisplayName(): ?string {
	// 	if (!$this->getUserId()) {
	// 		return null;
	// 	}

	// 	return Container::queryClass(IUserManager::class)->get($this->getUserId())?->getDisplayName()
	// 		?? $this->displayName
	// 		?? 'Deleted User';
	// }

	/**
	 * Returns email address
	 *
	 * - first tries to get emeil address from internal user
	 * - then get it from joined share
	 **/
	// public function getEmailAddress(): ?string {
	// 	if (!$this->getUserId()) {
	// 		return null;
	// 	}
	// 	return Container::queryClass(IUserManager::class)->get($this->getUserId())?->getEmailAddress()
	// 		?? $this->emailAddress;
	// }

	public function getUser(): array {
		/** @var UserMapper */
		$userMapper = (Container::queryClass(UserMapper::class));
		return $userMapper->getParticipant($this->getUserId(), $this->getPollId())->jsonSerialize();
	}
}
