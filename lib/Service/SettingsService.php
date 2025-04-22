<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\Model\Settings\AppSettings;

class SettingsService {

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private AppSettings $appSettings,
	) {
	}

	/**
	 * Get app settings with extended group information
	 */
	public function getAppSettings(): AppSettings {
		return $this->appSettings;
	}

	/**
	 * Write app settings
	 */
	public function writeAppSettings(array $settingsArray): void {
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_SHOW_MAIL_ADDRESSES, $settingsArray[AppSettings::SETTING_SHOW_MAIL_ADDRESSES]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_ALLOW_PUBLIC_SHARES, $settingsArray[AppSettings::SETTING_ALLOW_PUBLIC_SHARES]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_ALLOW_COMBO, $settingsArray[AppSettings::SETTING_ALLOW_COMBO]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_ALLOW_ALL_ACCESS, $settingsArray[AppSettings::SETTING_ALLOW_ALL_ACCESS]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_ALLOW_POLL_CREATION, $settingsArray[AppSettings::SETTING_ALLOW_POLL_CREATION]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_ALLOW_POLL_DOWNLOAD, $settingsArray[AppSettings::SETTING_ALLOW_POLL_DOWNLOAD]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_AUTO_ARCHIVE, $settingsArray[AppSettings::SETTING_AUTO_ARCHIVE]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_AUTO_DELETE, $settingsArray[AppSettings::SETTING_AUTO_DELETE]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_SHOW_LOGIN, $settingsArray[AppSettings::SETTING_SHOW_LOGIN]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_USE_ACTIVITY, $settingsArray[AppSettings::SETTING_USE_ACTIVITY]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_USE_SITE_LEGAL, $settingsArray[AppSettings::SETTING_USE_SITE_LEGAL]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_LEGAL_TERMS_IN_EMAIL, $settingsArray[AppSettings::SETTING_LEGAL_TERMS_IN_EMAIL]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_LOAD_POLLS_IN_NAVIGATION, $settingsArray[AppSettings::SETTING_LOAD_POLLS_IN_NAVIGATION]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_UNRESTRICTED_POLL_OWNER, $settingsArray[AppSettings::SETTING_UNRESTRICTED_POLL_OWNER]);

		$this->appSettings->setGroupSetting(AppSettings::SETTING_SHOW_MAIL_ADDRESSES_GROUPS, array_column($settingsArray[AppSettings::SETTING_SHOW_MAIL_ADDRESSES_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_ALLOW_ALL_ACCESS_GROUPS, array_column($settingsArray[AppSettings::SETTING_ALLOW_ALL_ACCESS_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_ALLOW_PUBLIC_SHARES_GROUPS, array_column($settingsArray[AppSettings::SETTING_ALLOW_PUBLIC_SHARES_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_ALLOW_COMBO_GROUPS, array_column($settingsArray[AppSettings::SETTING_ALLOW_COMBO_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_ALLOW_POLL_CREATION_GROUPS, array_column($settingsArray[AppSettings::SETTING_ALLOW_POLL_CREATION_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_ALLOW_POLL_DOWNLOAD_GROUPS, array_column($settingsArray[AppSettings::SETTING_ALLOW_POLL_DOWNLOAD_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_UNRESTRICTED_POLL_OWNER_GROUPS, array_column($settingsArray[AppSettings::SETTING_UNRESTRICTED_POLL_OWNER_GROUPS], 'id'));

		$this->appSettings->setIntegerSetting(AppSettings::SETTING_AUTO_ARCHIVE_OFFSET_DAYS, intval($settingsArray[AppSettings::SETTING_AUTO_ARCHIVE_OFFSET_DAYS]));
		$this->appSettings->setIntegerSetting(AppSettings::SETTING_AUTO_DELETE_OFFSET_DAYS, intval($settingsArray[AppSettings::SETTING_AUTO_DELETE_OFFSET_DAYS]));

		$this->appSettings->setStringSetting(AppSettings::SETTING_UPDATE_TYPE, $settingsArray[AppSettings::SETTING_UPDATE_TYPE]);
		$this->appSettings->setStringSetting(AppSettings::SETTING_PRIVACY_URL, $settingsArray[AppSettings::SETTING_PRIVACY_URL]);
		$this->appSettings->setStringSetting(AppSettings::SETTING_IMPRINT_URL, $settingsArray[AppSettings::SETTING_IMPRINT_URL]);
		$this->appSettings->setStringSetting(AppSettings::SETTING_DISCLAIMER, $settingsArray[AppSettings::SETTING_DISCLAIMER]);
	}
}
