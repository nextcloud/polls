<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\User;

use OCA\Polls\Helper\Container;
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
	private IUser $user;

	public function __construct(
		string $id,
		string $type = self::TYPE,
	) {
		parent::__construct($id, $type);
		$this->icon = self::ICON;
		$this->description = $this->l10n->t('User');

		$this->setUp();
	}

	/**
	 * setUp
	 */
	private function setUp(): void {
		$this->config = Container::queryClass(IConfig::class);
		$this->user = Container::queryClass(IUserManager::class)->get($this->id);
		$this->displayName = $this->user->getDisplayName();
		$this->emailAddress = (string)$this->user->getEmailAddress();
		$this->languageCode = $this->config->getUserValue($this->id, 'core', 'lang');
		$this->localeCode = $this->config->getUserValue($this->id, 'core', 'locale');
		$this->timeZoneName = $this->config->getUserValue($this->id, 'core', 'timezone');
	}

	public function isEnabled(): bool {
		return $this->user->isEnabled();
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
