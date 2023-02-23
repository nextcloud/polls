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

namespace OCA\Polls\Model\Settings;

use JsonSerializable;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Helper\Container;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;

class AppSettings implements JsonSerializable {
	private const APP_NAME = 'polls';
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

	/** @var IConfig */
	private $config;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserSession */
	private $session;

	/** @var string */
	private $userId = '';

	public function __construct() {
		$this->config = Container::queryClass(IConfig::class);
		$this->session = Container::queryClass(IUserSession::class);
		if ($this->session->isLoggedIn()) {
			$this->userId = Container::queryClass(IUserSession::class)->getUser()->getUId();
		}
		$this->groupManager = Container::queryClass(IGroupManager::class);
	}

	// Getters
	// generic Setters
	public function getBooleanSetting(string $key, bool $default = true): bool {
		return $this->stringToBool($this->config->getAppValue(self::APP_NAME, $key), $default);
	}

	public function getGroupSetting(string $key): array {
		return $this->stringToArray($this->config->getAppValue(self::APP_NAME, $key));
	}

	public function getStringSetting(string $key, string $default = ''): string {
		return $this->config->getAppValue(self::APP_NAME, $key) ?: $default;
	}

	public function getIntegerSetting(string $key, int $default = 0): int {
		return $this->stringToInteger($this->config->getAppValue(self::APP_NAME, $key), $default);
	}

	// Checks
	public function getPollCreationAllowed(): bool {
		if ($this->session->isLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_ALLOW_POLL_CREATION) || $this->isMember($this->getGroupSetting(self::SETTING_POLL_CREATION_GROUPS));
		}
		return false;
	}

	public function getAllowSeeMailAddresses(): bool {
		if ($this->session->isLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_SHOW_MAIL_ADDRESSES) || $this->isMember($this->getGroupSetting(self::SETTING_SHOW_MAIL_ADDRESSES_GROUPS));
		}
		return false;
	}

	public function getPollDownloadAllowed(): bool {
		if ($this->session->isLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_ALLOW_POLL_DOWNLOAD) || $this->isMember($this->getGroupSetting(self::SETTING_POLL_DOWNLOAD_GROUPS));
		}
		return false;
	}

	public function getAllAccessAllowed(): bool {
		if ($this->session->isLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_ALLOW_ALL_ACCESS) || $this->isMember($this->getGroupSetting(self::SETTING_ALL_ACCESS_GROUPS));
		}
		return false;
	}

	public function getPublicSharesAllowed(): bool {
		if ($this->session->isLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_ALLOW_PUBLIC_SHARES) || $this->isMember($this->getGroupSetting(self::SETTING_PUBLIC_SHARES_GROUPS));
		}
		return false;
	}

	public function getComboAllowed(): bool {
		if ($this->session->isLoggedIn()) {
			return $this->getBooleanSetting(self::SETTING_ALLOW_COMBO)
			  || $this->isMember($this->getGroupSetting(self::SETTING_COMBO_GROUPS));
		}
		return false;
	}

	public function getUsePrivacyUrl(): string {
		if ($this->config->getAppValue(self::APP_NAME, self::SETTING_PRIVACY_URL)) {
			return $this->config->getAppValue(self::APP_NAME, self::SETTING_PRIVACY_URL);
		}
		return $this->config->getAppValue('theming', 'privacyUrl');
	}

	public function getUseImprintUrl(): string {
		if ($this->config->getAppValue(self::APP_NAME, self::SETTING_IMPRINT_URL)) {
			return $this->config->getAppValue(self::APP_NAME, self::SETTING_IMPRINT_URL);
		}
		return $this->config->getAppValue('theming', 'imprintUrl');
	}
	
	public function getAutoarchiveOffset() {
		return $this->getIntegerSetting(self::SETTING_AUTO_ARCHIVE_OFFSET, self::SETTING_AUTO_ARCHIVE_OFFSET_DEFAULT);
	}

	public function getUpdateType() {
		return $this->getStringSetting(self::SETTING_UPDATE_TYPE, self::SETTING_UPDATE_TYPE_DEFAULT);
	}

	// Setters
	// generic setters
	public function setBooleanSetting(string $key, bool $value): void {
		$this->config->setAppValue(self::APP_NAME, $key, $this->boolToString($value));
	}

	public function setGroupSetting(string $key, array $value): void {
		$this->config->setAppValue(self::APP_NAME, $key, json_encode($value));
	}

	public function setStringSetting(string $key, string $value): void {
		$this->config->setAppValue(self::APP_NAME, $key, $value);
	}

	/**
	 * @return array
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
			if ($this->groupManager->isInGroup($this->userId, $GID)) {
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
		switch ($value) {
			case 'yes':
				return true;
			case 'no':
				return false;
			default:
				return $default;
		}
	}

	private function boolToString(?bool $value): string {
		if ($value) {
			return 'yes';
		}
		return 'no';
	}
}
