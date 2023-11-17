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

use OCA\Polls\Model\Settings\AppSettings;

class SettingsService {
	private AppSettings $appSettings;

	public function __construct() {
		$this->appSettings = new AppSettings;
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
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_SHOW_LOGIN, $settingsArray[AppSettings::SETTING_SHOW_LOGIN]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_USE_ACTIVITY, $settingsArray[AppSettings::SETTING_USE_ACTIVITY]);
		$this->appSettings->setBooleanSetting(AppSettings::SETTING_LEGAL_TERMS_IN_EMAIL, $settingsArray[AppSettings::SETTING_LEGAL_TERMS_IN_EMAIL]);
		
		$this->appSettings->setGroupSetting(AppSettings::SETTING_SHOW_MAIL_ADDRESSES_GROUPS, array_column($settingsArray[AppSettings::SETTING_SHOW_MAIL_ADDRESSES_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_ALL_ACCESS_GROUPS, array_column($settingsArray[AppSettings::SETTING_ALL_ACCESS_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_PUBLIC_SHARES_GROUPS, array_column($settingsArray[AppSettings::SETTING_PUBLIC_SHARES_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_COMBO_GROUPS, array_column($settingsArray[AppSettings::SETTING_COMBO_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_POLL_CREATION_GROUPS, array_column($settingsArray[AppSettings::SETTING_POLL_CREATION_GROUPS], 'id'));
		$this->appSettings->setGroupSetting(AppSettings::SETTING_POLL_DOWNLOAD_GROUPS, array_column($settingsArray[AppSettings::SETTING_POLL_DOWNLOAD_GROUPS], 'id'));
		
		$this->appSettings->setStringSetting(AppSettings::SETTING_AUTO_ARCHIVE_OFFSET, strval($settingsArray[AppSettings::SETTING_AUTO_ARCHIVE_OFFSET]));
		$this->appSettings->setStringSetting(AppSettings::SETTING_UPDATE_TYPE, $settingsArray[AppSettings::SETTING_UPDATE_TYPE]);
		$this->appSettings->setStringSetting(AppSettings::SETTING_PRIVACY_URL, $settingsArray[AppSettings::SETTING_PRIVACY_URL]);
		$this->appSettings->setStringSetting(AppSettings::SETTING_IMPRINT_URL, $settingsArray[AppSettings::SETTING_IMPRINT_URL]);
		$this->appSettings->setStringSetting(AppSettings::SETTING_DISCLAIMER, $settingsArray[AppSettings::SETTING_DISCLAIMER]);
	}
}
