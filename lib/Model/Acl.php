<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
			'appPermissions' => $this->getPermissionsArray(),
			'appSettings' => $this->getAppSettings(),
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

	/**
	 * Get all permissions as array
	 */
	private function getPermissionsArray(): array {
		return [
			'allAccess' => $this->getIsAllowed(self::PERMISSION_ALL_ACCESS),
			'publicShares' => $this->getIsAllowed(self::PERMISSION_PUBLIC_SHARES),
			'pollCreation' => $this->getIsAllowed(self::PERMISSION_POLL_CREATE),
			'seeMailAddresses' => $this->getIsAllowed(self::PERMISSION_POLL_MAILADDRESSES_VIEW),
			'pollDownload' => $this->getIsAllowed(self::PERMISSION_POLL_DOWNLOAD),
		];
	}

	private function getAppSettings(): array {
		$appSettingsArray = [
			'usePrivacyUrl' => '',
			'useImprintUrl' => '',
			'useLogin' => true,
			'useActivity' => false,
			'navigationPollsInList' => false,
			'updateType' => $this->appSettings->getUpdateType(),
		];

		if ($this->userSession->getIsLoggedIn()) {
			return array_merge($appSettingsArray, $this->getInternalAppSettings());
		}

		return array_merge($appSettingsArray, $this->getPublicAppSettings());
	}

	/**
	 * Get public app settings
	 */
	private function getPublicAppSettings(): array {
		return [
			'usePrivacyUrl' => $this->appSettings->getUsePrivacyUrl(),
			'useImprintUrl' => $this->appSettings->getUseImprintUrl(),
			'useLogin' => $this->appSettings->getShowLogin(),
		];
	}

	/**
	 * Get internal app settings
	 */
	private function getInternalAppSettings(): array {
		return [
			'useActivity' => $this->appSettings->getUseActivity(),
			'navigationPollsInList' => $this->appSettings->getLoadPollsInNavigation(),
		];
	}
	/**
	 * loads the current user from the userSession or returns the cached one
	 */
	private function getCurrentUser(): UserBase {
		return $this->userSession->getUser();
	}

	/**
	 * Shortcut for UserSession::getCurrentUserId()
	 */
	public function getCurrentUserId(): string {
		return $this->userSession->getCurrentUserId();
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
