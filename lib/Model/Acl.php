<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 René Gieling <github@dartcafe.de>
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

use JsonSerializable;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\UserSession;

/**
 * Class Acl
 *
 * @package OCA\Polls\Model\Acl
 */
class Acl implements JsonSerializable {
	public const PERMISSION_OVERRIDE = 'override_permission';
	public const PERMISSION_ALL_ACCESS = 'allAccess';
	public const PERMISSION_PUBLIC_SHARES = 'publicShares';
	public const PERMISSION_POLL_CREATE = 'pollCreate';
	public const PERMISSION_POLL_MAILADDRESSES_VIEW = 'seeMailAddresses';
	public const PERMISSION_POLL_DOWNLOAD = 'pollDownload';
	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private AppSettings $appSettings,
		private UserSession $userSession,
	) {
	}

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {
		return	[
			'currentUser' => $this->userSession->getUser(),
			'permissions' => $this->getPermissionsArray(),
		];
	}

	/**
	 * Check perticular rights and inform via boolean value, if the right is granted  or denied
	 */
	public function getIsAllowed(string $permission): bool {
		return match ($permission) {
			self::PERMISSION_OVERRIDE => true,
			self::PERMISSION_ALL_ACCESS => $this->appSettings->getAllAccessAllowed(),
			self::PERMISSION_PUBLIC_SHARES => $this->appSettings->getPublicSharesAllowed(),
			self::PERMISSION_POLL_CREATE => $this->appSettings->getPollCreationAllowed(),
			self::PERMISSION_POLL_MAILADDRESSES_VIEW => $this->appSettings->getAllowSeeMailAddresses(),
			self::PERMISSION_POLL_DOWNLOAD => $this->appSettings->getPollDownloadAllowed(),
			default => false,
		};
	}

	public function getPermissionsArray(): array {
		return [
			'allAccess' => $this->getIsAllowed(self::PERMISSION_ALL_ACCESS),
			'publicShares' => $this->getIsAllowed(self::PERMISSION_PUBLIC_SHARES),
			'pollCreation' => $this->getIsAllowed(self::PERMISSION_POLL_CREATE),
			'seeMailAddresses' => $this->getIsAllowed(self::PERMISSION_POLL_MAILADDRESSES_VIEW),
			'pollDownload' => $this->getIsAllowed(self::PERMISSION_POLL_DOWNLOAD),
		];
	}

	/**
	 * loads the current user from the userSession or returns the cached one
	 */
	private function getCurrentUser(): UserBase {
		return $this->userSession->getUser();
	}

	/**
	 * Shortcut for currentUser->userId
	 */
	public function getUserId(): string {
		return $this->getCurrentUser()->getId();
	}

	/**
	 * Request a permission level and get exception if denied
	 * @throws ForbiddenException Thrown if access is denied
	 */
	public function request(string $permission): void {
		if (!$this->getIsAllowed($permission)) {
			throw new ForbiddenException('denied permission ' . $permission);
		}
	}

	/**
	 * Compare $userId with current user's id
	 */
	public function matchUser(string $userId): bool {
		return $this->getCurrentUser()->getId() === $userId;
	}
}
