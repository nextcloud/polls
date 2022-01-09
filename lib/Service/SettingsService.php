<?php
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

use OCP\IConfig;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Model\UserGroup\Group;

class SettingsService {

	/** @var IConfig */
	private $config;

	/** @var AppSettings */
	private $appSettings;

	/** @var string|null */
	private $userId;

	public function __construct(
		?string $UserId,
		IConfig $config
	) {
		$this->userId = $UserId;
		$this->config = $config;
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
		$this->appSettings->setAllowPublicShares($settingsArray['allowPublicShares']);
		$this->appSettings->setAllowAllAccess($settingsArray['allowAllAccess']);
		$this->appSettings->setAllowPollCreation($settingsArray['allowPollCreation']);
		$this->appSettings->setAllowPollDownload($settingsArray['allowPollDownload']);
		$this->appSettings->setShowLogin($settingsArray['showLogin']);
		$this->appSettings->setAutoArchive($settingsArray['autoArchive']);
		$this->appSettings->setAutoArchiveOffset($settingsArray['autoArchiveOffset']);
		$this->appSettings->setAllAccessGroups(array_column($settingsArray['allAccessGroups'], 'id'));
		$this->appSettings->setPublicSharesGroups(array_column($settingsArray['publicSharesGroups'], 'id'));
		$this->appSettings->setPollCreationGroups(array_column($settingsArray['pollCreationGroups'], 'id'));
		$this->appSettings->setPollDownloadGroups(array_column($settingsArray['pollDownloadGroups'], 'id'));
		$this->appSettings->setUpdateType($settingsArray['updateType']);
		$this->appSettings->setUseActivity($settingsArray['useActivity']);
	}
}
