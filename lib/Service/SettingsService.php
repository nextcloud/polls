<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\AppConstants;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\IAppConfig;

class SettingsService {
	
	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private AppSettings $appSettings,
		private IAppConfig $appConfig,
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
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_SHOW_MAIL_ADDRESSES, $settingsArray[AppSettings::SETTING_SHOW_MAIL_ADDRESSES]);
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_ALLOW_PUBLIC_SHARES, $settingsArray[AppSettings::SETTING_ALLOW_PUBLIC_SHARES]);
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_ALLOW_COMBO, $settingsArray[AppSettings::SETTING_ALLOW_COMBO]);
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_ALLOW_ALL_ACCESS, $settingsArray[AppSettings::SETTING_ALLOW_ALL_ACCESS]);
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_ALLOW_POLL_CREATION, $settingsArray[AppSettings::SETTING_ALLOW_POLL_CREATION]);
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_ALLOW_POLL_DOWNLOAD, $settingsArray[AppSettings::SETTING_ALLOW_POLL_DOWNLOAD]);
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_AUTO_ARCHIVE, $settingsArray[AppSettings::SETTING_AUTO_ARCHIVE]);
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_SHOW_LOGIN, $settingsArray[AppSettings::SETTING_SHOW_LOGIN]);
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_USE_ACTIVITY, $settingsArray[AppSettings::SETTING_USE_ACTIVITY]);
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_LEGAL_TERMS_IN_EMAIL, $settingsArray[AppSettings::SETTING_LEGAL_TERMS_IN_EMAIL]);
		$this->appConfig->setValueBool(AppConstants::APP_ID, AppSettings::SETTING_LOAD_POLLS_IN_NAVIGATION, $settingsArray[AppSettings::SETTING_LOAD_POLLS_IN_NAVIGATION]);

		$this->appConfig->setValueArray(AppConstants::APP_ID, AppSettings::SETTING_SHOW_MAIL_ADDRESSES_GROUPS, array_column($settingsArray[AppSettings::SETTING_SHOW_MAIL_ADDRESSES_GROUPS], 'id'));
		$this->appConfig->setValueArray(AppConstants::APP_ID, AppSettings::SETTING_ALL_ACCESS_GROUPS, array_column($settingsArray[AppSettings::SETTING_ALL_ACCESS_GROUPS], 'id'));
		$this->appConfig->setValueArray(AppConstants::APP_ID, AppSettings::SETTING_PUBLIC_SHARES_GROUPS, array_column($settingsArray[AppSettings::SETTING_PUBLIC_SHARES_GROUPS], 'id'));
		$this->appConfig->setValueArray(AppConstants::APP_ID, AppSettings::SETTING_COMBO_GROUPS, array_column($settingsArray[AppSettings::SETTING_COMBO_GROUPS], 'id'));
		$this->appConfig->setValueArray(AppConstants::APP_ID, AppSettings::SETTING_POLL_CREATION_GROUPS, array_column($settingsArray[AppSettings::SETTING_POLL_CREATION_GROUPS], 'id'));
		$this->appConfig->setValueArray(AppConstants::APP_ID, AppSettings::SETTING_POLL_DOWNLOAD_GROUPS, array_column($settingsArray[AppSettings::SETTING_POLL_DOWNLOAD_GROUPS], 'id'));
		
		$this->appConfig->setValueInt(AppConstants::APP_ID, AppSettings::SETTING_AUTO_ARCHIVE_OFFSET, $settingsArray[AppSettings::SETTING_AUTO_ARCHIVE_OFFSET]);

		$this->appConfig->setValueString(AppConstants::APP_ID, AppSettings::SETTING_UPDATE_TYPE, $settingsArray[AppSettings::SETTING_UPDATE_TYPE]);
		$this->appConfig->setValueString(AppConstants::APP_ID, AppSettings::SETTING_PRIVACY_URL, $settingsArray[AppSettings::SETTING_PRIVACY_URL]);
		$this->appConfig->setValueString(AppConstants::APP_ID, AppSettings::SETTING_IMPRINT_URL, $settingsArray[AppSettings::SETTING_IMPRINT_URL]);
		$this->appConfig->setValueString(AppConstants::APP_ID, AppSettings::SETTING_DISCLAIMER, $settingsArray[AppSettings::SETTING_DISCLAIMER]);
	}
}
