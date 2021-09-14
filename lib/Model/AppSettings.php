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

namespace OCA\Polls\Model;

use JsonSerializable;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCA\Polls\AppInfo\Application;

class AppSettings implements JsonSerializable {
	private const APP_NAME = 'polls';

	/** @var IConfig */
	private $config;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserSession */
	private $session;

	/** @var string */
	private $userId = '';

	public function __construct() {
		$this->config = self::getContainer()->query(IConfig::class);
		$this->session = self::getContainer()->query(IUserSession::class);
		if ($this->session->isLoggedIn()) {
			$this->userId = self::getContainer()->query(IUserSession::class)->getUser()->getUId();
		}
		$this->groupManager = self::getContainer()->query(IGroupManager::class);
	}

	// Getters
	public function getAllowPublicShares(): bool {
		return $this->stringToBool($this->config->getAppValue(self::APP_NAME, 'publicSharesGroups'), true);
	}

	public function getAllowAllAccess(): bool {
		return $this->stringToBool($this->config->getAppValue(self::APP_NAME, 'allowAllAccess'), true);
	}

	public function getAllowPollCreation(): bool {
		return $this->stringToBool($this->config->getAppValue(self::APP_NAME, 'allowPollCreation'), true);
	}

	public function getPublicSharesGroups(): array {
		return $this->stringToArray($this->config->getAppValue(self::APP_NAME, 'publicSharesGroups'));
	}

	public function getAllAccessGroups(): array {
		return $this->stringToArray($this->config->getAppValue(self::APP_NAME, 'allAccessGroups'));
	}

	public function getPollCreationGroups(): array {
		return $this->stringToArray($this->config->getAppValue(self::APP_NAME, 'pollCreationGroups'));
	}

	public function getShowLogin(): bool {
		return $this->stringToBool($this->config->getAppValue(self::APP_NAME, 'showLogin'), true);
	}

	public function getAutoArchive(): bool {
		return $this->stringToBool($this->config->getAppValue(self::APP_NAME, 'autoArchive'), false);
	}

	public function getAutoArchiveOffset(): int {
		return $this->stringToInteger($this->config->getAppValue(self::APP_NAME, 'autoArchiveOffset'), 30);
	}

	// Checks
	public function getCreationAllowed(): bool {
		if ($this->session->isLoggedIn()) {
			return $this->getAllowPollCreation() || $this->isMember($this->getPollCreationGroups());
		}
		return false;
	}

	public function getAllAccessAllowed(): bool {
		if ($this->session->isLoggedIn()) {
			return $this->getAllowAllAccess() || $this->isMember($this->getAllAccessGroups());
		}
		return false;
	}

	public function getPublicSharesAllowed(): bool {
		if ($this->session->isLoggedIn()) {
			return $this->getAllowPublicShares() || $this->isMember($this->getPublicSharesGroups());
		}
		return false;
	}

	// Setters
	public function setAllowPublicShares(bool $value): void {
		$this->config->setAppValue(self::APP_NAME, 'allowPublicShares', $this->BoolToString($value));
	}

	public function setShowLogin(bool $value): void {
		$this->config->setAppValue(self::APP_NAME, 'showLogin', $this->BoolToString($value));
	}

	public function setAllowAllAccess(bool $value): void {
		$this->config->setAppValue(self::APP_NAME, 'allowAllAccess', $this->BoolToString($value));
	}

	public function setAllowPollCreation(bool $value): void {
		$this->config->setAppValue(self::APP_NAME, 'allowPollCreation', $this->BoolToString($value));
	}

	public function setPublicSharesGroups(array $value): void {
		$this->config->setAppValue(self::APP_NAME, 'publicSharesGroups', json_encode($value));
	}

	public function setAllAccessGroups(array $value): void {
		$this->config->setAppValue(self::APP_NAME, 'allAccessGroups', json_encode($value));
	}

	public function setPollCreationGroups(array $value): void {
		$this->config->setAppValue(self::APP_NAME, 'pollCreationGroups', json_encode($value));
	}

	public function setAutoArchive(bool $value): void {
		$this->config->setAppValue(self::APP_NAME, 'autoArchive', $this->BoolToString($value));
	}

	public function setAutoArchiveOffset(int $value): void {
		$this->config->setAppValue(self::APP_NAME, 'autoArchiveOffset', strval($value));
	}

	public function jsonSerialize() {
		// convert group ids to group objects
		$publicSharesGroups = [];
		$allAccessGroups = [];
		$pollCreationGroups = [];

		foreach ($this->getPublicSharesGroups() as $group) {
			$publicSharesGroups[] = new Group($group);
		}

		foreach ($this->getAllAccessGroups() as $group) {
			$allAccessGroups[] = new Group($group);
		}

		foreach ($this->getPollCreationGroups() as $group) {
			$pollCreationGroups[] = new Group($group);
		}

		return [
			'allowPublicShares' => $this->getAllowPublicShares(),
			'allowAllAccess' => $this->getAllowAllAccess(),
			'allowPollCreation' => $this->getAllowPollCreation(),
			'allAccessGroups' => $allAccessGroups,
			'pollCreationGroups' => $pollCreationGroups,
			'publicSharesGroups' => $publicSharesGroups,
			'showLogin' => $this->getShowLogin(),
			'autoArchive' => $this->getAutoArchive(),
			'autoArchiveOffset' => $this->getAutoArchiveOffset(),
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
		if ($appConfig !== '') {
			return intval($appConfig);
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

	private function BoolToString(bool $value): string {
		if ($value) {
			return 'yes';
		}
		return 'no';
	}


	protected static function getContainer() {
		$app = \OC::$server->query(Application::class);
		return $app->getContainer();
	}
}
