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

namespace OCA\Polls\Db;

use JsonSerializable;
use OCA\Polls\AppConstants;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\IURLGenerator;

/**
 * @method int getId()
 * @method void setId(integer $value)
 * @method string getToken()
 * @method void setToken(string $value)
 * @method string getType()
 * @method void setType(string $value)
 * @method int getPollId()
 * @method void setPollId(integer $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method string getEmailAddress()
 * @method void setEmailAddress(string $value)
 * @method int getInvitationSent()
 * @method void setInvitationSent(integer $value)
 * @method int getReminderSent()
 * @method void setReminderSent(integer $value)
 * @method int getLocked()
 * @method void setLocked(integer $value)
 * @method string getDisplayName()
 * @method void setDisplayName(string $value)
 * @method string getMiscSettings()
 * @method void setMiscSettings(string $value)
 * @method int getVoted()
 * @method void setVoted(int $value)
 * @method int getDeleted()
 * @method void setDeleted(integer $value)
 */
class Share extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_share';

	public const EMAIL_OPTIONAL = 'optional';
	public const EMAIL_MANDATORY = 'mandatory';
	public const EMAIL_DISABLED = 'disabled';

	// Only authenticated access
	public const TYPE_USER = 'user';
	public const TYPE_ADMIN = 'admin';
	public const TYPE_GROUP = 'group';

	// Public and authenticated Access
	public const TYPE_PUBLIC = 'public';

	// Only public access
	public const TYPE_EMAIL = 'email';
	public const TYPE_CONTACT = 'contact';
	public const TYPE_EXTERNAL = 'external';

	// no direct Access
	public const TYPE_CIRCLE = 'circle';
	public const TYPE_CONTACTGROUP = 'contactGroup';


	// Share types, that are allowed for public access (without login)
	public const SHARE_PUBLIC_ACCESS_ALLOWED = [
		self::TYPE_PUBLIC,
		self::TYPE_CONTACT,
		self::TYPE_EMAIL,
		self::TYPE_EXTERNAL,
	];

	// Share types, that are allowed for authenticated access (with login)
	public const SHARE_AUTH_ACCESS_ALLOWED = [
		self::TYPE_PUBLIC,
		self::TYPE_ADMIN,
		self::TYPE_GROUP,
		self::TYPE_USER,
	];

	public const TYPE_SORT_ARRAY = [
		self::TYPE_PUBLIC,
		self::TYPE_ADMIN,
		self::TYPE_GROUP,
		self::TYPE_USER,
		self::TYPE_CONTACT,
		self::TYPE_EMAIL,
		self::TYPE_EXTERNAL,
		self::TYPE_CIRCLE,
		self::TYPE_CONTACTGROUP,
	];
	public const RESOLVABLE_SHARES = [
		self::TYPE_CIRCLE,
		self::TYPE_CONTACTGROUP
	];

	public $id = null;
	protected IURLGenerator $urlGenerator;
	protected AppSettings $appSettings;
	protected int $pollId = 0;
	protected string $token = '';
	protected string $type = '';
	protected string $userId = '';
	protected ?string $emailAddress = null;
	protected int $invitationSent = 0;
	protected int $reminderSent = 0;
	protected int $locked = 0;
	protected ?string $displayName = null;
	protected ?string $miscSettings = '';
	protected int $voted = 0;
	protected int $deleted = 0;
	protected string $label = '';

	public function __construct() {
		$this->addType('pollId', 'int');
		$this->addType('invitationSent', 'int');
		$this->addType('locked', 'int');
		$this->addType('reminderSent', 'int');
		$this->addType('deleted', 'int');
		$this->urlGenerator = Container::queryClass(IURLGenerator::class);
		$this->appSettings = new AppSettings;
	}

	/**
	 * @return array
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'token' => $this->getToken(),
			'type' => $this->getType(),
			'pollId' => $this->getPollId(),
			'userId' => $this->getUserId(),
			'emailAddress' => $this->getEmailAddress(),
			'invitationSent' => $this->getInvitationSent(),
			'reminderSent' => $this->getReminderSent(),
			'locked' => $this->getDeleted() ? 0 : $this->getLocked(),
			'label' => $this->getLabel(),
			'URL' => $this->getURL(),
			'showLogin' => $this->appSettings->getBooleanSetting(AppSettings::SETTING_SHOW_LOGIN),
			'publicPollEmail' => $this->getPublicPollEmail(),
			'voted' => $this->getVoted(),
			'deleted' => $this->getDeleted(),
			'user' => $this->getUser(),
		];
	}

	/**
	 * Setting, if email is optional, mandatory or hidden on public poll registration
	 */
	public function getPublicPollEmail(): string {
		return $this->getMiscSettingsArray()['publicPollEmail'] ?? 'optional';
	}

	/**
	 * Setting, if email is optional, mandatory or hidden on public poll registration
	 */
	public function setPublicPollEmail(string $value): void {
		$this->setMiscSettingsByKey('publicPollEmail', $value);
	}

	/**
	 * Share label for public shares, falls back to username until migrated
	 * TODO: remove fallback after migration was introduced
	 */
	public function getLabel(): string {
		if ($this->getType() === self::TYPE_PUBLIC && $this->label) {
			return $this->label;
		}
		return $this->displayName ?? '';
	}

	/**
	 * Sharee's displayName. In case of public poll label is used instead
	 * TODO: remove public poll chaeck after migration to label
	 */
	public function getDisplayName(): string {
		if ($this->getType() === self::TYPE_PUBLIC) {
			return '';
		}
		return (string) $this->displayName;
	}

	public function getTimeZoneName(): string {
		return $this->getMiscSettingsArray()['timeZone'] ?? '';
	}

	public function setTimeZoneName(string $value): void {
		$this->setMiscSettingsByKey('timeZone', $value);
	}

	public function getLanguage(): string {
		return $this->getMiscSettingsArray()['language'] ?? '';
	}

	public function setLanguage(string $value): void {
		$this->setMiscSettingsByKey('language', $value);
	}
	
	// Fallback for now; use language as locale
	public function getLocale(): string {
		return $this->getLanguage();
	}

	public function getURL(): string {
		if (in_array($this->type, [self::TYPE_USER, self::TYPE_ADMIN, self::TYPE_GROUP], true)) {
			return $this->urlGenerator->linkToRouteAbsolute(
				AppConstants::APP_ID . '.page.vote',
				['id' => $this->pollId]
			);
		} elseif ($this->token) {
			return $this->urlGenerator->linkToRouteAbsolute(
				AppConstants::APP_ID . '.public.vote_page',
				['token' => $this->token]
			);
		} else {
			return '';
		}
	}

	public function getUserId(): string {
		return $this->userId;
	}

	public function getRichObjectString(): array {
		return [
			'type' => 'highlight',
			'id' => $this->getId(),
			'name' => $this->getType(),
		];
	}

	private function setMiscSettingsArray(array $value): void {
		$this->setMiscSettings(json_encode($value));
	}

	private function getMiscSettingsArray(): array {
		if ($this->getMiscSettings()) {
			return json_decode($this->getMiscSettings(), true);
		}
		return [];
	}

	/**
	 * @param bool|string|int|array $value
	 */
	private function setMiscSettingsByKey(string $key, $value): void {
		$miscSettings = $this->getMiscSettingsArray();
		$miscSettings[$key] = $value;
		$this->setMiscSettingsArray($miscSettings);
	}
}
