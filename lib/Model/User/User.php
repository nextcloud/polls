<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Model\User;

use OCA\Polls\Helper\Container;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Model\UserBase;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;

class User extends UserBase {
	/** @var string */
	public const TYPE = 'user';
	/** @var string */
	public const ICON = 'icon-user';
	/** @var string */
	public const PRINCIPAL_PREFIX = 'principals/users/';

	private IConfig $config;
	protected AppSettings $appSettings;
	private IUser $user;

	public function __construct(
		string $id,
		string $type = self::TYPE
	) {
		parent::__construct($id, $type);
		$this->icon = self::ICON;
		$this->isNoUser = false;
		$this->description = $this->l10n->t('User');

		$this->config = Container::queryClass(IConfig::class);
		$this->user = Container::queryClass(IUserManager::class)->get($this->id);
		$this->displayName = $this->user->getDisplayName();
		$this->emailAddress = $this->user->getEmailAddress();
		$this->languageCode = $this->config->getUserValue($this->id, 'core', 'lang');
		$this->localeCode = $this->config->getUserValue($this->id, 'core', 'locale');
		$this->timeZoneName = $this->config->getUserValue($this->id, 'core', 'timezone');
		$this->appSettings = new AppSettings;
	}

	public function isEnabled(): bool {
		return $this->user->isEnabled();
	}

	public function getEmailAddressMasked(): string {
		if ($this->appSettings->getAllowSeeMailAddresses() && $this->emailAddress) {
			return $this->emailAddress;
		}
		return '';
	}

	public function getDescription(): string {
		if ($this->getEmailAddress()) {
			return $this->getEmailAddress();
		}
		return $this->description;
	}

	public function getPrincipalUri(): string {
		return self::PRINCIPAL_PREFIX . $this->getId();
	}
	/**
	 * @return User[]
	 *
	 * @psalm-return list<User>
	 */
	public static function search(string $query = '', array $skip = []): array {
		$users = [];

		foreach (Container::queryClass(IUserManager::class)->search($query) as $user) {
			if (!in_array($user->getUID(), $skip)) {
				$users[] = new self($user->getUID());
			}
		}
		return $users;
	}
}
