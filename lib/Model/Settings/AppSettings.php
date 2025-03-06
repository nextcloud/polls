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
use OCP\IConfig;

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
		private IConfig $config,
		private UserSession $userSession,
	) {
	}

	// Getters
	// generic Setters
	public function getBooleanSetting(string $key, bool $default = true): bool {
		return $this->stringToBool($this->config->getAppValue(AppConstants::APP_ID, $key), $default);
	}

	public function getGroupSetting(string $key): array {
		return $this->stringToArray($this->config->getAppValue(AppConstants::APP_ID, $key));
	}

	public function getStringSetting(string $key, string $default = ''): string {
		return $this->config->getAppValue(AppConstants::APP_ID, $key) ?: $default;
	}

	public function getIntegerSetting(string $key, int $default = 0): int {
		return $this->stringToInteger($this->config->getAppValue(AppConstants::APP_ID, $key), $default);
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

	/**
	 * Permission to create shares is controlled by core settings
	 */
	public function getShareCreateAllowed(): bool {
		if (!$this->userSession->getIsLoggedIn()) {
			// only logged in users can create shares
			return false;
		}

		// first check group exception mode
		$groupExceptionMode = $this->getShareGroupExceptionMode();

		if ($groupExceptionMode === 'off') {
			// no group exceptions are set, allow share creation
			return true;

			// TODO: get clarification for the usage of the excluded groups
			// The following code is not reachable by intention, because it is based on a misunderstanding.
			// just kept for the moment as reminder
			//
			// Additionally check, if user is in excluded groups 'Exclude groups from creating link shares'
			$excludedGroups = $this->getShareExcludedGroups();
			if ($excludedGroups) {
				// if user is in exception group, allow share creation
				return !$this->userSession->getUser()->getIsInGroupArray($excludedGroups);
			}
		}

		// get group exceptions
		$exceptionGroups = $this->getShareExceptionGroups();

		if ($groupExceptionMode === 'allowGroup') {
			// exception mode is 'Limit sharing to some groups'
			// if user is in exception group, allow share creation
			return $this->userSession->getUser()->getIsInGroupArray($exceptionGroups);
		} elseif ($groupExceptionMode === 'denyGroup') {
			// exception mode is 'Exclude some Groups from sharing'
			// if user is in exception group, deny share creation
			return !$this->userSession->getUser()->getIsInGroupArray($exceptionGroups);
		}

		return true;
	}

	/**
	 * Get share exception groups
	 * @return array
	 */
	public function getShareExceptionGroups(): array {
		$exceptionGroups = $this->config->getAppValue('core', 'shareapi_exclude_groups_list', '');
		return json_decode($exceptionGroups, true) ?? [];
	}

	/**
	 * Get share excluded groups
	 * @return array
	 */
	public function getShareExcludedGroups(): array {
		$excludedGroups = $this->config->getAppValue('core', 'shareapi_allow_links_exclude_groups', '');
		return json_decode($excludedGroups, true) ?? [];
	}

	/**
	 * Get share group exception mode
	 * @return string
	 * @psalm-return 'denyGroup'|'allowGroup'|'off'
	 * Take value from the core setting 'shareapi_exclude_groups' and translate
	 * 'yes' => 'denyGroup' ('Exclude some groups from sharing')
	 * 'allow' => 'allowGroup' (Limit sharing to some groups)
	 * default => 'off' (Allow sharing for everyone) or setting absent
	 */
	public function getShareGroupExceptionMode(): string {
		$excludedMode = $this->config->getAppValue('core', 'shareapi_exclude_groups', '');
		return match ($excludedMode) {
			'yes' => 'denyGroup',
			'allow' => 'allowGroup',
			default => 'off',
		};
	}

	public function getUsePrivacyUrl(): string {
		if ($this->config->getAppValue(AppConstants::APP_ID, self::SETTING_PRIVACY_URL)) {
			return $this->config->getAppValue(AppConstants::APP_ID, self::SETTING_PRIVACY_URL);
		}
		return $this->config->getAppValue('theming', 'privacyUrl');
	}

	public function getUseImprintUrl(): string {
		if ($this->config->getAppValue(AppConstants::APP_ID, self::SETTING_IMPRINT_URL)) {
			return $this->config->getAppValue(AppConstants::APP_ID, self::SETTING_IMPRINT_URL);
		}
		return $this->config->getAppValue('theming', 'imprintUrl');
	}

	public function getAutoarchiveOffset(): int {
		return $this->getIntegerSetting(self::SETTING_AUTO_ARCHIVE_OFFSET, self::SETTING_AUTO_ARCHIVE_OFFSET_DEFAULT);
	}

	public function getUpdateType(): string {
		return $this->getStringSetting(self::SETTING_UPDATE_TYPE, self::SETTING_UPDATE_TYPE_DEFAULT);
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

	// Setters
	// generic setters
	public function setBooleanSetting(string $key, bool $value): void {
		$this->config->setAppValue(AppConstants::APP_ID, $key, $this->boolToString($value));
	}

	public function setGroupSetting(string $key, array $value): void {
		$this->config->setAppValue(AppConstants::APP_ID, $key, json_encode($value));
	}

	public function setStringSetting(string $key, string $value): void {
		$this->config->setAppValue(AppConstants::APP_ID, $key, $value);
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
			self::SETTING_DISCLAIMER => $this->getStringSetting(self::SETTING_DISCLAIMER),
			self::SETTING_IMPRINT_URL => $this->getStringSetting(self::SETTING_IMPRINT_URL),
			self::SETTING_PRIVACY_URL => $this->getStringSetting(self::SETTING_PRIVACY_URL),
			self::SETTING_UPDATE_TYPE => $this->getUpdateType(),
			'usePrivacyUrl' => $this->getUsePrivacyUrl(),
			'useImprintUrl' => $this->getUseImprintUrl(),
			'defaultPrivacyUrl' => $this->config->getAppValue('theming', 'privacyUrl'),
			'defaultImprintUrl' => $this->config->getAppValue('theming', 'imprintUrl'),
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

	private function boolToString(?bool $value): string {
		if ($value) {
			return 'yes';
		}
		return 'no';
	}
}
