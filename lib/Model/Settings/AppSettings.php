<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\Settings;

use JsonSerializable;
use OCA\Polls\AppConstants;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\UserSession;
use OCP\IAppConfig;

class AppSettings implements JsonSerializable {
	public const SETTING_ALLOW_PUBLIC_SHARES = 'allowPublicShares';
	public const SETTING_ALLOW_COMBO = 'allowCombo';
	public const SETTING_ALLOW_ALL_ACCESS = 'allowAllAccess';
	public const SETTING_ALLOW_POLL_CREATION = 'allowPollCreation';
	public const SETTING_ALLOW_POLL_DOWNLOAD = 'allowPollDownload';
	public const SETTING_AUTO_ARCHIVE = 'autoArchive';
	public const SETTING_LEGAL_TERMS_IN_EMAIL = 'legalTermsInEmail';
	public const SETTING_SHOW_LOGIN = 'showLogin';
	public const SETTING_USE_ACTIVITY = 'useActivity';
	// new
	public const SETTING_ALL_ACCESS_GROUPS = 'allAccessGroups';
	public const SETTING_POLL_CREATION_GROUPS = 'pollCreationGroups';
	public const SETTING_POLL_DOWNLOAD_GROUPS = 'pollDownloadGroups';
	public const SETTING_PUBLIC_SHARES_GROUPS = 'publicSharesGroups';
	public const SETTING_SHOW_MAIL_ADDRESSES_GROUPS = 'showMailAddressesGroups';
	public const SETTING_COMBO_GROUPS = 'comboGroups';
	public const SETTING_LOAD_POLLS_IN_NAVIGATION = 'navigationPollsInList';

	public const SETTING_SHOW_MAIL_ADDRESSES = 'showMailAddresses';
	public const SETTING_AUTO_ARCHIVE_OFFSET = 'autoArchiveOffset';
	public const SETTING_AUTO_ARCHIVE_OFFSET_DEFAULT = 30;
	public const SETTING_UPDATE_TYPE = 'updateType';
	public const SETTING_PRIVACY_URL = 'privacyUrl';
	public const SETTING_IMPRINT_URL = 'imprintUrl';
	public const SETTING_DISCLAIMER = 'disclaimer';

	public const SETTING_UPDATE_TYPE_LONG_POLLING = 'longPolling';
	public const SETTING_UPDATE_TYPE_NO_POLLING = 'noPolling';
	public const SETTING_UPDATE_TYPE_PERIODIC_POLLING = 'periodicPolling';
	public const SETTING_UPDATE_TYPE_DEFAULT = self::SETTING_UPDATE_TYPE_NO_POLLING;

	public function __construct(
		private IAppConfig $appConfig,
		private UserSession $userSession,
	) {
	}

	/**
	 * Get all permissions as array
	 */
	public function getPermissionsArray(): array {
		return [
			'allAccess' => $this->getAllAccessAllowed(),
			'publicShares' => $this->getPublicSharesAllowed(),
			'pollCreation' => $this->getPollCreationAllowed(),
			'seeMailAddresses' => $this->getAllowSeeMailAddresses(),
			'pollDownload' => $this->getPollDownloadAllowed(),
			'comboView' => $this->getComboAllowed(),
		];
	}

	public function getAppSettings(): array {
		$appSettingsArray = [
			'usePrivacyUrl' => '',
			'useImprintUrl' => '',
			'useLogin' => true,
			'useActivity' => false,
			'navigationPollsInList' => false,
			'updateType' => $this->getUpdateType(),
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
			'usePrivacyUrl' => $this->getUsePrivacyUrl(),
			'useImprintUrl' => $this->getUseImprintUrl(),
			'useLogin' => $this->getShowLogin(),
		];
	}

	/**
	 * Get internal app settings
	 */
	private function getInternalAppSettings(): array {
		return [
			'useActivity' => $this->getUseActivity(),
			'navigationPollsInList' => $this->getLoadPollsInNavigation(),
		];
	}

	private function checkSettingType(string $key, int $type): bool {
		return $this->appConfig->getValueType(AppConstants::APP_ID, $key) === $type;
	}

	// Getters
	// generic Setters
	public function getBooleanSetting(string $key, bool $default = true): bool {
		if ($this->checkSettingType($key, IAppConfig::VALUE_BOOL)) {
			return $this->appConfig->getValueBool(AppConstants::APP_ID, $key);
		}
		return $this->stringToBool($this->appConfig->getValueString(AppConstants::APP_ID, $key), $default);
	}

	public function getGroupSetting(string $key): array {
		if ($this->checkSettingType($key, IAppConfig::VALUE_ARRAY)) {
			return $this->appConfig->getValueArray(AppConstants::APP_ID, $key, []);
		}
		return $this->stringToArray($this->appConfig->getValueString(AppConstants::APP_ID, $key));
	}

	public function getIntegerSetting(string $key, int $default = 0): int {
		if ($this->checkSettingType($key, IAppConfig::VALUE_INT)) {
			return $this->appConfig->getValueInt(AppConstants::APP_ID, $key, $default);
		}
		return $this->stringToInteger($this->appConfig->getValueString(AppConstants::APP_ID, $key), $default);
	}

	// Checks
	/**
	 * Poll creation permission is controlled by app settings
	 */
	public function getPollCreationAllowed(): bool {
		if ($this->userSession->getIsLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_ALLOW_POLL_CREATION) || $this->isMember($this->getGroupSetting(self::SETTING_POLL_CREATION_GROUPS));
		}
		return false;
	}

	/**
	 * Permission to see emailaddresses is controlled by app settings
	 */
	public function getAllowSeeMailAddresses(): bool {
		if ($this->userSession->getIsLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_SHOW_MAIL_ADDRESSES) || $this->isMember($this->getGroupSetting(self::SETTING_SHOW_MAIL_ADDRESSES_GROUPS));
		}
		return false;
	}

	/**
	 * Permission to download emailaddresses is controlled by app settings
	 */
	public function getPollDownloadAllowed(): bool {
		if ($this->userSession->getIsLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_ALLOW_POLL_DOWNLOAD) || $this->isMember($this->getGroupSetting(self::SETTING_POLL_DOWNLOAD_GROUPS));
		}
		return false;
	}

	/**
	 * Permission to share polls with all internal users is controlled by app settings (open poll)
	 */
	public function getAllAccessAllowed(): bool {
		if ($this->userSession->getIsLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_ALLOW_ALL_ACCESS) || $this->isMember($this->getGroupSetting(self::SETTING_ALL_ACCESS_GROUPS));
		}
		return false;
	}

	/**
	 * Permission to create public shares is controlled by app settings
	 */
	public function getPublicSharesAllowed(): bool {
		if ($this->userSession->getIsLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_ALLOW_PUBLIC_SHARES) || $this->isMember($this->getGroupSetting(self::SETTING_PUBLIC_SHARES_GROUPS));
		}
		return false;
	}

	/**
	 * Permission to combine polls is controlled by app settings and only for internal users
	 */
	public function getComboAllowed(): bool {
		if ($this->userSession->getIsLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_ALLOW_COMBO)
			  || $this->isMember($this->getGroupSetting(self::SETTING_COMBO_GROUPS));
		}
		return false;
	}

	public function getUsePrivacyUrl(): string {
		$ownSetting = $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_PRIVACY_URL);
		if ($ownSetting === '') {
			return $ownSetting;
		}
		return $this->appConfig->getValueString('theming', 'privacyUrl');
	}

	public function getUseImprintUrl(): string {
		$ownSetting = $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_IMPRINT_URL);
		if ($ownSetting === '') {
			return $ownSetting;
		}
		return $this->appConfig->getValueString('theming', 'imprintUrl');
	}

	public function getAutoarchiveOffset(): int {
		return $this->getIntegerSetting(self::SETTING_AUTO_ARCHIVE_OFFSET, self::SETTING_AUTO_ARCHIVE_OFFSET_DEFAULT);
	}

	public function getUpdateType(): string {
		return $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_UPDATE_TYPE, self::SETTING_UPDATE_TYPE_DEFAULT);
	}

	public function getShowLogin(): bool {
		return $this->getBooleanSetting(self::SETTING_SHOW_LOGIN);
	}

	public function getUseActivity(): bool {
		return $this->getBooleanSetting(self::SETTING_USE_ACTIVITY);
	}
	public function getLoadPollsInNavigation(): bool {
		return $this->getBooleanSetting(self::SETTING_LOAD_POLLS_IN_NAVIGATION);
	}

	/**
	 * @return array
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {
		// convert group ids to group objects
		$publicSharesGroups = [];
		$comboGroups = [];
		$allAccessGroups = [];
		$pollCreationGroups = [];
		$pollDownloadGroups = [];
		$showMailAddressesGroups = [];

		foreach ($this->getGroupSetting(self::SETTING_PUBLIC_SHARES_GROUPS) as $group) {
			$publicSharesGroups[] = new Group($group);
		}

		foreach ($this->getGroupSetting(self::SETTING_COMBO_GROUPS) as $group) {
			$comboGroups[] = new Group($group);
		}

		foreach ($this->getGroupSetting(self::SETTING_ALL_ACCESS_GROUPS) as $group) {
			$allAccessGroups[] = new Group($group);
		}

		foreach ($this->getGroupSetting(self::SETTING_POLL_CREATION_GROUPS) as $group) {
			$pollCreationGroups[] = new Group($group);
		}

		foreach ($this->getGroupSetting(self::SETTING_POLL_DOWNLOAD_GROUPS) as $group) {
			$pollDownloadGroups[] = new Group($group);
		}

		foreach ($this->getGroupSetting(self::SETTING_SHOW_MAIL_ADDRESSES_GROUPS) as $group) {
			$showMailAddressesGroups[] = new Group($group);
		}

		return [
			self::SETTING_ALLOW_PUBLIC_SHARES => $this->getBooleanSetting(self::SETTING_ALLOW_PUBLIC_SHARES),
			self::SETTING_ALLOW_COMBO => $this->getBooleanSetting(self::SETTING_ALLOW_COMBO),
			self::SETTING_ALLOW_ALL_ACCESS => $this->getBooleanSetting(self::SETTING_ALLOW_ALL_ACCESS),
			self::SETTING_ALLOW_POLL_CREATION => $this->getBooleanSetting(self::SETTING_ALLOW_POLL_CREATION),
			self::SETTING_ALLOW_POLL_DOWNLOAD => $this->getBooleanSetting(self::SETTING_ALLOW_POLL_DOWNLOAD),
			self::SETTING_LEGAL_TERMS_IN_EMAIL => $this->getBooleanSetting(self::SETTING_LEGAL_TERMS_IN_EMAIL),
			self::SETTING_LOAD_POLLS_IN_NAVIGATION => $this->getBooleanSetting(self::SETTING_LOAD_POLLS_IN_NAVIGATION),
			self::SETTING_SHOW_LOGIN => $this->getBooleanSetting(self::SETTING_SHOW_LOGIN),
			self::SETTING_SHOW_MAIL_ADDRESSES => $this->getBooleanSetting(self::SETTING_SHOW_MAIL_ADDRESSES),
			self::SETTING_USE_ACTIVITY => $this->getBooleanSetting(self::SETTING_USE_ACTIVITY),
			self::SETTING_ALL_ACCESS_GROUPS => $allAccessGroups,
			self::SETTING_POLL_CREATION_GROUPS => $pollCreationGroups,
			self::SETTING_POLL_DOWNLOAD_GROUPS => $pollDownloadGroups,
			self::SETTING_PUBLIC_SHARES_GROUPS => $publicSharesGroups,
			self::SETTING_SHOW_MAIL_ADDRESSES_GROUPS => $showMailAddressesGroups,
			self::SETTING_COMBO_GROUPS => $comboGroups,
			self::SETTING_AUTO_ARCHIVE => $this->getBooleanSetting(self::SETTING_AUTO_ARCHIVE),
			self::SETTING_AUTO_ARCHIVE_OFFSET => $this->getAutoarchiveOffset(),
			self::SETTING_DISCLAIMER => $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_DISCLAIMER),
			self::SETTING_IMPRINT_URL => $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_IMPRINT_URL),
			self::SETTING_PRIVACY_URL => $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_PRIVACY_URL),
			self::SETTING_UPDATE_TYPE => $this->getUpdateType(),
			'usePrivacyUrl' => $this->getUsePrivacyUrl(),
			'useImprintUrl' => $this->getUseImprintUrl(),
			'defaultPrivacyUrl' => $this->appConfig->getValueString('theming', 'privacyUrl'),
			'defaultImprintUrl' => $this->appConfig->getValueString('theming', 'imprintUrl'),
		];
	}

	private function isMember(array $groups): bool {
		foreach ($groups as $GID) {
			if ($this->userSession->getUser()->getIsInGroup($GID)) {
				return true;
			}
		}
		return false;
	}

	private function stringToInteger(string $value, int $default): int {
		if ($value !== '') {
			return intval($value);
		}
		return $default;
	}

	private function stringToArray(string $value): array {
		if ($value) {
			return json_decode($value);
		}
		return [];
	}

	private function stringToBool(string $value, bool $default): bool {
		return match ($value) {
			'yes' => true,
			'no' => false,
			default => $default,
		};
	}
}
