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

	/** @var bool */
	private $allowPublicShares = true;

	/** @var bool */
	private $showLogin = true;

	/** @var bool */
	private $allowAllAccess = true;

	/** @var bool */
	private $allowPollCreation = true;

	/** @var bool */
	private $autoArchive = true;

	/** @var integer */
	private $autoArchiveOffset = 30; // default to 30 days

	/** @var array */
	private $publicSharesGroups = [];

	/** @var array */
	private $allAccessGroups = [];

	/** @var array */
	private $pollCreationGroups = [];

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
		return !!$this->config->getAppValue(self::APP_NAME, 'allowPublicShares');
	}

	public function getAllowAllAccess(): bool {
		return !!$this->config->getAppValue(self::APP_NAME, 'allowAllAccess');
	}

	public function getAllowPollCreation(): bool {
		return !!$this->config->getAppValue(self::APP_NAME, 'allowPollCreation');
	}

	public function getPublicSharesGroups(): array {
		return json_decode($this->config->getAppValue(self::APP_NAME, 'publicSharesGroups'));
	}

	public function getAllAccessGroups(): array {
		return json_decode($this->config->getAppValue(self::APP_NAME, 'allAccessGroups'));
	}

	public function getPollCreationGroups(): array {
		return json_decode($this->config->getAppValue(self::APP_NAME, 'pollCreationGroups'));
	}

	public function getShowLogin(): bool {
		return !!$this->config->getAppValue(self::APP_NAME, 'showLogin');
	}

	public function getAutoArchive(): bool {
		return !!$this->config->getAppValue(self::APP_NAME, 'autoArchive');
	}

	public function getAutoArchiveOffset(): int {
		return intval($this->config->getAppValue(self::APP_NAME, 'autoArchiveOffset'));
	}

	// Checks
	public function getCreationAllowed() {
		if ($this->session->isLoggedIn()) {
			return $this->getAllowPollCreation() || $this->isMember($this->getPollCreationGroups());
		}
		return false;
	}

	public function getAllAccessAllowed() {
		if ($this->session->isLoggedIn()) {
			return $this->getAllowAllAccess() || $this->isMember($this->getAllAccessGroups());
		}
		return false;
	}

	public function getPublicSharesAllowed() {
		if ($this->session->isLoggedIn()) {
			return $this->getAllowPublicShares() || $this->isMember($this->getPublicSharesGroups());
		}
		return false;
	}

	// Setters
	public function setAllowPublicShares(bool $value) {
		$this->config->setAppValue(self::APP_NAME, 'allowPublicShares', strval($value));
	}

	public function setShowLogin(bool $value) {
		$this->config->setAppValue(self::APP_NAME, 'showLogin', strval($value));
	}

	public function setAllowAllAccess(bool $value) {
		$this->config->setAppValue(self::APP_NAME, 'allowAllAccess', strval($value));
	}

	public function setAllowPollCreation(bool $value) {
		$this->config->setAppValue(self::APP_NAME, 'allowPollCreation', strval($value));
	}

	public function setPublicSharesGroups(array $value) {
		$this->config->setAppValue(self::APP_NAME, 'publicSharesGroups', json_encode($value));
	}

	public function setAllAccessGroups(array $value) {
		$this->config->setAppValue(self::APP_NAME, 'allAccessGroups', json_encode($value));
	}

	public function setPollCreationGroups(array $value) {
		$this->config->setAppValue(self::APP_NAME, 'pollCreationGroups', json_encode($value));
	}

	public function setAutoArchive(bool $value) {
		$this->config->setAppValue(self::APP_NAME, 'autoArchive', strval($value));
	}

	public function setAutoArchiveOffset(int $value) {
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

	private function isMember(array $groups) {
		foreach ($groups as $GID) {
			if ($this->groupManager->isInGroup($this->userId, $GID)) {
				return true;
			}
		}
		return false;
	}

	protected static function getContainer() {
		$app = \OC::$server->query(Application::class);
		return $app->getContainer();
	}
}
