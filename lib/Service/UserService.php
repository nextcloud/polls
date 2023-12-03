<?php

declare(strict_types=1);
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

namespace OCA\Polls\Service;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Model\UserBase;

class UserService {
	
	public function __construct(
		private UserMapper $userMapper,
	) {
	}

	/**
	 * Get current userId
	 *
	 * Returns userId of the current (share|nextcloud) user
	 **/
	public function getCurrentUserId(): string {
		return $this->userMapper->getCurrentUserId();
	}
	/**
	 * Get current user
	 *
	 * Returns a UserBase child for the current (share|nextcloud) user based on
	 * - the session stored share token or
	 * - the user session stored userId
	 * and stores userId to session
	 *
	 * !! The share is prioritised to tell User from Admin class
	 */
	public function getCurrentUser(): UserBase {
		return $this->userMapper->getCurrentUser();

	}

	public function isLoggedIn(): bool {
		return $this->userMapper->isLoggedIn();
	}

	/**
	 * Get poll participant
	 *
	 * Returns a UserBase child from share determined by userId and pollId
	 *
	 * @param string $userId Get internal user. If pollId is given, the user who participates in the particulair poll will be returned
	 * @param int $pollId Can only be used together with $userId and will return the internal user or the share user
	 * @return UserBase
	 **/
	public function getParticipant(string $userId, int $pollId = null): UserBase {
		return $this->userMapper->getParticipant($userId, $pollId);
	}

	/**
	 * Get participans of a poll as array of user objects
	 * @return UserBase[]
	 */
	public function getParticipants(int $pollId): array {
		return $this->userMapper->getParticipants($pollId);
	}

	/**
	 * Get participans of a poll as array of user objects
	 *
	 * Returns a UserBase child build from a share
	 */
	public function getUserFromShare(Share $share): UserBase {
		return $this->userMapper->getUserFromShare($share);
	}

	public function getUserFromUserBase(string $userId): UserBase {
		return $this->userMapper->getUserFromUserBase($userId);
	}

	public function getUserObject(string $type, string $id, string $displayName = '', ?string $emailAddress = '', string $language = '', string $locale = '', string $timeZoneName = ''): UserBase {
		return $this->userMapper->getUserObject($type, $id, $displayName, $emailAddress, $language, $locale, $timeZoneName);
	}
}
