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
use Psr\Log\LoggerInterface;

class AppSettings implements JsonSerializable {
	private const NO_GROUPCHECK = '';

	public const SETTING_ALLOW_PUBLIC_SHARES = 'allowPublicShares';
	public const SETTING_ALLOW_PUBLIC_SHARES_GROUPS = 'publicSharesGroups';

	public const SETTING_ALLOW_COMBO = 'allowCombo';
	public const SETTING_ALLOW_COMBO_GROUPS = 'comboGroups';

	public const SETTING_ALLOW_ALL_ACCESS = 'allowAllAccess';
	public const SETTING_ALLOW_ALL_ACCESS_GROUPS = 'allAccessGroups';

	public const SETTING_ALLOW_POLL_CREATION = 'allowPollCreation';
	public const SETTING_ALLOW_POLL_CREATION_GROUPS = 'pollCreationGroups';

	public const SETTING_ALLOW_POLL_DOWNLOAD = 'allowPollDownload';
	public const SETTING_ALLOW_POLL_DOWNLOAD_GROUPS = 'pollDownloadGroups';

	public const SETTING_SHOW_MAIL_ADDRESSES = 'showMailAddresses';
	public const SETTING_SHOW_MAIL_ADDRESSES_GROUPS = 'showMailAddressesGroups';

	public const SETTING_UNRESTRICTED_OWNER = 'unrestrictedOwner';
	public const SETTING_UNRESTRICTED_OWNER_GROUPS = 'unrestrictedOwnerGroups';

	public const SETTING_LEGAL_TERMS_IN_EMAIL = 'legalTermsInEmail';
	public const SETTING_DISCLAIMER = 'disclaimer';
	public const SETTING_PRIVACY_URL = 'privacyUrl';
	public const SETTING_IMPRINT_URL = 'imprintUrl';

	public const SETTING_SHOW_LOGIN = 'showLogin';
	public const SETTING_USE_ACTIVITY = 'useActivity';
	public const SETTING_LOAD_POLLS_IN_NAVIGATION = 'navigationPollsInList';

	public const SETTING_AUTO_ARCHIVE = 'autoArchive';
	public const SETTING_AUTO_ARCHIVE_DEFAULT = false;
	public const SETTING_AUTO_ARCHIVE_OFFSET_DAYS = 'autoArchiveOffset';
	public const SETTING_AUTO_ARCHIVE_OFFSET_DAYS_DEFAULT = 30;


	public const SETTING_UPDATE_TYPE = 'updateType';
	public const SETTING_UPDATE_TYPE_LONG_POLLING = 'longPolling';
	public const SETTING_UPDATE_TYPE_NO_POLLING = 'noPolling';
	public const SETTING_UPDATE_TYPE_PERIODIC_POLLING = 'periodicPolling';
	public const SETTING_UPDATE_TYPE_DEFAULT = self::SETTING_UPDATE_TYPE_NO_POLLING;

	public function __construct(
		private IAppConfig $appConfig,
		private UserSession $userSession,
		private SystemSettings $systemSettings,
		protected LoggerInterface $logger,
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
			'unrestrictedOwner' => $this->getUnrestrictedOwner(),
			'addShares' => $this->systemSettings->getShareCreateAllowed(),
			'addSharesExternal' => $this->systemSettings->getShareCreateAllowed(),
		];
	}

	public function getAppSettings(): array {
		$appSettingsArray = [
			'finalPrivacyUrl' => '',
			'finalImprintUrl' => '',
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
			'finalPrivacyUrl' => $this->getFinalPrivacyUrl(),
			'finalImprintUrl' => $this->getFinalImprintUrl(),
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

	private function checkSettingType(string $key, int $expectedType, string $app = AppConstants::APP_ID): bool {
		try {
			$actualType = $this->appConfig->getValueType($app, $key);
			if ($actualType === $expectedType || $actualType === IAppConfig::VALUE_MIXED) {
				return true;
			}

			$this->logger->warning('Setting type does not match', [
				'app' => $app,
				'key' => $key,
				'expectedType' => $expectedType,
				'actualType' => $actualType,
			]);

		} catch (\Exception $e) {
			$this->logger->warning('Could not get setting type', [
				'app' => $app,
				'key' => $key,
				'expectedType' => $expectedType,
				'actualType' => null,
				'exception' => $e->getMessage()
			]);
		}
		return false;
	}

	// Getters
	// generic Setters

	/**
	 * Get the value of a boolean setting
	 */
	private function getBooleanSetting(string $key, string $groupKey = self::NO_GROUPCHECK, bool $default = true, string $app = AppConstants::APP_ID): bool {
		// key missing or invalid, return default
		if (!$this->checkSettingType($key, IAppConfig::VALUE_BOOL, $app)) {
			return $default;
		}

		// no group check or user is not logged in, just return the boolean setting value
		if ($groupKey === self::NO_GROUPCHECK || !$this->userSession->getIsLoggedIn()) {
			return $this->appConfig->getValueBool($app, $key, $default);
		}

		// user is logged in and group check is required
		return $this->appConfig->getValueBool($app, $key, $default) || $this->isMember($this->getGroupSetting($groupKey));
	}

	/**
	 * Set a boolean setting
	 */
	public function setBooleanSetting(string $key, bool $value): void {
		$this->appConfig->setValueBool(AppConstants::APP_ID, $key, $value);
	}

	/**
	 * Get the value of an group (array) setting
	 */
	private function getGroupSetting(string $key, array $default = [], string $app = AppConstants::APP_ID): array {
		if (!$this->checkSettingType($key, IAppConfig::VALUE_ARRAY, $app)) {
			return $default;
		}
		return $this->appConfig->getValueArray($app, $key, $default);
	}

	/**
	 * Set an array setting
	 */
	public function setGroupSetting(string $key, array $value): void {
		$this->appConfig->setValueArray(AppConstants::APP_ID, $key, $value);
	}

	/**
	 * Get the value of an integer setting
	 */
	private function getIntegerSetting(string $key, int $default = 0, string $app = AppConstants::APP_ID): int {
		if (!$this->checkSettingType($key, IAppConfig::VALUE_INT, $app)) {
			return $default;
		}
		return $this->appConfig->getValueInt($app, $key, $default);
	}

	/**
	 * Set a integer setting
	 */
	public function setIntegerSetting(string $key, int $value): void {
		$this->appConfig->setValueInt(AppConstants::APP_ID, $key, $value);
	}

	/**
	 * Get the value of a string setting
	 */
	private function getStringSetting(string $key, string $default = '', string $app = AppConstants::APP_ID): string {
		if (!$this->checkSettingType($key, IAppConfig::VALUE_STRING, $app)) {
			return $default;
		}
		return $this->appConfig->getValueString($app, $key, $default);
	}

	/**
	 * Set a string setting
	 */
	public function setStringSetting(string $key, string $value): void {
		$this->appConfig->setValueString(AppConstants::APP_ID, $key, $value);
	}

	// Checks
	/**
	 * Poll creation permission is controlled by app settings
	 */
	public function getPollCreationAllowed(): bool {
		return $this->getBooleanSetting(self::SETTING_ALLOW_POLL_CREATION, self::SETTING_ALLOW_POLL_CREATION_GROUPS);
	}

	/**
	 * Poll creation permission is controlled by app settings
	 */
	public function getUnrestrictedOwner(): bool {
		return $this->getBooleanSetting(self::SETTING_UNRESTRICTED_OWNER, self::SETTING_UNRESTRICTED_OWNER_GROUPS);
	}

	/**
	 * Permission to see emailaddresses is controlled by app settings
	 */
	public function getAllowSeeMailAddresses(): bool {
		return $this->getBooleanSetting(self::SETTING_SHOW_MAIL_ADDRESSES, self::SETTING_SHOW_MAIL_ADDRESSES_GROUPS);
	}

	/**
	 * Permission to download emailaddresses is controlled by app settings
	 */
	public function getPollDownloadAllowed(): bool {
		return $this->getBooleanSetting(self::SETTING_ALLOW_POLL_DOWNLOAD,self::SETTING_ALLOW_POLL_DOWNLOAD_GROUPS);
	}

	/**
	 * Permission to share polls with all internal users is controlled by app settings (open poll)
	 */
	public function getAllAccessAllowed(): bool {
		return $this->getBooleanSetting(self::SETTING_ALLOW_ALL_ACCESS, self::SETTING_ALLOW_ALL_ACCESS_GROUPS);
	}

	/**
	 * Permission to create public shares is controlled by app settings
	 */
	public function getPublicSharesAllowed(): bool {
		return $this->getBooleanSetting(self::SETTING_ALLOW_PUBLIC_SHARES, self::SETTING_ALLOW_PUBLIC_SHARES_GROUPS);
	}

	/**
	 * Permission to combine polls is controlled by app settings and only for internal users
	 */
	public function getComboAllowed(): bool {
		return $this->getBooleanSetting(self::SETTING_ALLOW_COMBO, self::SETTING_ALLOW_COMBO_GROUPS);
	}

	/**
	 * Get privacy url
	 * Returns the final privacy url to use
	 * Use URL from the app settings if set, otherwise use the default from the theming app
	 */
	public function getFinalPrivacyUrl(): string {
		$privacyUrl = $this->getStringSetting(self::SETTING_PRIVACY_URL);
		if ($privacyUrl) {
			return $privacyUrl;
		}

		return $this->getStringSetting(key: 'privacyUrl', app: 'theming');
	}

	/**
	 * Get imprint url
	 * Returns the imprint url from the app settings if set,
	 * otherwise the default from theming
	 */
	public function getFinalImprintUrl(): string {
		$imprintUrl = $this->getStringSetting(self::SETTING_IMPRINT_URL);
		if ($imprintUrl) {
			return $imprintUrl;
		}

		return $this->getStringSetting(key: 'imprintUrl', app: 'theming');
	}

	/**
	 * Get wether link to the imprint and privacy terms should be used in email footers
	 */
	public function getUseLegalTermsInEmail(): bool {
		return $this->getBooleanSetting(self::SETTING_LEGAL_TERMS_IN_EMAIL);
	}

	/**
	 * Get the disclaimer text
	 */
	public function getDisclaimer(): string {
		return $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_DISCLAIMER);
	}

	/**
	 * Get the auto archive offset in days
	 */
	public function getAutoarchiveOffsetDays(): int {
		return $this->getIntegerSetting(self::SETTING_AUTO_ARCHIVE_OFFSET_DAYS, self::SETTING_AUTO_ARCHIVE_OFFSET_DAYS_DEFAULT);
	}

	/**
	 * Get the auto archive setting enabled or disabled
	 */
	public function getAutoarchiveEnabled(): bool {
		return $this->getBooleanSetting(self::SETTING_AUTO_ARCHIVE, default: self::SETTING_AUTO_ARCHIVE_DEFAULT);
	}

	/**
	 * Get the update type for frontend polling of new data
	 * returning one of the following:
	 * - longPolling
	 * - noPolling
	 * - periodicPolling
	 */
	public function getUpdateType(): string {
		return $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_UPDATE_TYPE, self::SETTING_UPDATE_TYPE_DEFAULT);
	}

	/**
	 * Get wether to show login button in the public register dialog
	 */
	public function getShowLogin(): bool {
		return $this->getBooleanSetting(self::SETTING_SHOW_LOGIN);
	}

	/**
	 * Get wether to use Activity app or not
	 */
	public function getUseActivity(): bool {
		return $this->getBooleanSetting(self::SETTING_USE_ACTIVITY);
	}

	/**
	 * Get wether to show polls in the navigation
	 */
	public function getLoadPollsInNavigation(): bool {
		return $this->getBooleanSetting(self::SETTING_LOAD_POLLS_IN_NAVIGATION);
	}

	/**
	 * Get the group objects for the given group ids
	 * @param array $groupIds
	 * @return Group[]
	 * @psalm-return array<Group>
	 */
	private function getGroupObjects(string $settingsGroup): array {
		$groups = [];
		$groupIds = $this->getGroupSetting($settingsGroup);

		foreach ($groupIds as $group) {
			$groups[] = new Group($group);
		}

		return $groups;
	}

	/**
	 * @return array
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {

		return [
			self::SETTING_ALLOW_PUBLIC_SHARES => $this->getBooleanSetting(self::SETTING_ALLOW_PUBLIC_SHARES),
			self::SETTING_ALLOW_PUBLIC_SHARES_GROUPS => $this->getGroupObjects(self::SETTING_ALLOW_PUBLIC_SHARES_GROUPS),

			self::SETTING_ALLOW_COMBO => $this->getBooleanSetting(self::SETTING_ALLOW_COMBO),
			self::SETTING_ALLOW_COMBO_GROUPS => $this->getGroupObjects(self::SETTING_ALLOW_COMBO_GROUPS),

			self::SETTING_ALLOW_ALL_ACCESS => $this->getBooleanSetting(self::SETTING_ALLOW_ALL_ACCESS),
			self::SETTING_ALLOW_ALL_ACCESS_GROUPS => $this->getGroupObjects(self::SETTING_ALLOW_ALL_ACCESS_GROUPS),

			self::SETTING_ALLOW_POLL_CREATION => $this->getBooleanSetting(self::SETTING_ALLOW_POLL_CREATION),
			self::SETTING_ALLOW_POLL_CREATION_GROUPS => $this->getGroupObjects(self::SETTING_ALLOW_POLL_CREATION_GROUPS),

			self::SETTING_ALLOW_POLL_DOWNLOAD => $this->getBooleanSetting(self::SETTING_ALLOW_POLL_DOWNLOAD),
			self::SETTING_ALLOW_POLL_DOWNLOAD_GROUPS => $this->getGroupObjects(self::SETTING_ALLOW_POLL_DOWNLOAD_GROUPS),

			self::SETTING_UNRESTRICTED_OWNER => $this->getBooleanSetting(self::SETTING_UNRESTRICTED_OWNER),
			self::SETTING_UNRESTRICTED_OWNER_GROUPS => $this->getGroupObjects(self::SETTING_UNRESTRICTED_OWNER_GROUPS),

			self::SETTING_SHOW_MAIL_ADDRESSES => $this->getBooleanSetting(self::SETTING_SHOW_MAIL_ADDRESSES),
			self::SETTING_SHOW_MAIL_ADDRESSES_GROUPS => $this->getGroupObjects(self::SETTING_SHOW_MAIL_ADDRESSES_GROUPS),

			self::SETTING_AUTO_ARCHIVE => $this->getBooleanSetting(self::SETTING_AUTO_ARCHIVE),
			self::SETTING_AUTO_ARCHIVE_OFFSET_DAYS => $this->getAutoarchiveOffsetDays(),

			self::SETTING_LEGAL_TERMS_IN_EMAIL => $this->getBooleanSetting(self::SETTING_LEGAL_TERMS_IN_EMAIL),
			self::SETTING_DISCLAIMER => $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_DISCLAIMER),
			self::SETTING_IMPRINT_URL => $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_IMPRINT_URL),
			self::SETTING_PRIVACY_URL => $this->appConfig->getValueString(AppConstants::APP_ID, self::SETTING_PRIVACY_URL),
			self::SETTING_UPDATE_TYPE => $this->getUpdateType(),

			self::SETTING_USE_ACTIVITY => $this->getBooleanSetting(self::SETTING_USE_ACTIVITY),
			self::SETTING_LOAD_POLLS_IN_NAVIGATION => $this->getBooleanSetting(self::SETTING_LOAD_POLLS_IN_NAVIGATION),
			self::SETTING_SHOW_LOGIN => $this->getBooleanSetting(self::SETTING_SHOW_LOGIN),

			'finalPrivacyUrl' => $this->getFinalPrivacyUrl(),
			'finalImprintUrl' => $this->getFinalImprintUrl(),
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
}
