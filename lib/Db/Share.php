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

namespace OCA\Polls\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;
use OCP\IURLGenerator;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Helper\Container;

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
 * @method string getDisplayName()
 * @method void setDisplayName(string $value)
 * @method string getMiscSettings()
 * @method void setMiscSettings(string $value)
 */
class Share extends Entity implements JsonSerializable {
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

	/** @var string $token */
	protected $token = '';

	/** @var string $type */
	protected $type = '';

	/** @var int $pollId */
	protected $pollId = 0;

	/** @var string $userId */
	protected $userId = '';

	/** @var string $emailAddress */
	protected $emailAddress = '';

	/** @var string $invitationSent */
	protected $invitationSent = '';

	/** @var string $reminderSent */
	protected $reminderSent = '';

	/** @var string $displayName */
	protected $displayName = '';

	/** @var string $miscSettings*/
	protected $miscSettings;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var PollMapper */
	protected $pollMapper;

	/** @var AppSettings */
	protected $appSettings;

	public function __construct() {
		$this->addType('pollId', 'int');
		$this->addType('invitationSent', 'int');
		$this->addType('reminderSent', 'int');
		$this->urlGenerator = Container::queryClass(IURLGenerator::class);
		$this->appSettings = new AppSettings;
	}

	public function jsonSerialize(): mixed {
		return [
			'id' => $this->getId(),
			'token' => $this->getToken(),
			'type' => $this->getType(),
			'pollId' => $this->getPollId(),
			'userId' => $this->getUserId(),
			'emailAddress' => $this->getEmailAddress(),
			'invitationSent' => $this->getInvitationSent(),
			'reminderSent' => $this->getReminderSent(),
			'displayName' => $this->getDisplayName(),
			'isNoUser' => !(in_array($this->getType(), [self::TYPE_USER, self::TYPE_ADMIN], true)),
			'URL' => $this->getURL(),
			'showLogin' => $this->appSettings->getBooleanSetting(AppSettings::SETTING_SHOW_LOGIN),
			'publicPollEmail' => $this->getPublicPollEmail(),
		];
	}

	public function getPublicPollEmail(): string {
		return $this->getMiscSettingsArray()['publicPollEmail'] ?? $this->getDefaultPublicPollEmail();
	}

	public function setPublicPollEmail(string $value) : void {
		$this->setMiscSettingsByKey('publicPollEmail', $value);
	}

	public function getTimeZoneName() : ?string {
		return $this->getMiscSettingsArray()['timeZone'] ?? '';
	}

	public function setTimeZoneName(string $value) : void {
		$this->setMiscSettingsByKey('timeZone', $value);
	}

	public function getLanguage(): ?string {
		return $this->getMiscSettingsArray()['language'] ?? '';
	}

	// Fallback for now; use language as locale
	public function getLocale(): ?string {
		return $this->getLanguage();
	}

	public function setLanguage(string $value) : void {
		$this->setMiscSettingsByKey('language', $value);
	}

	public function getURL(): string {
		if (in_array($this->type, [self::TYPE_USER, self::TYPE_ADMIN, self::TYPE_GROUP], true)) {
			return $this->urlGenerator->linkToRouteAbsolute(
				'polls.page.vote',
				['id' => $this->pollId]
			);
		} elseif ($this->token) {
			return $this->urlGenerator->linkToRouteAbsolute(
				'polls.public.vote_page',
				['token' => $this->token]
			);
		} else {
			return '';
		}
	}

	public function getUserId(): string {
		if ($this->type === self::TYPE_CONTACTGROUP) {
			// contactsgroup had the prefix contactgroup_ until version 1.5
			// strip it out
			$parts = explode("contactgroup_", $this->userId);
			$userId = end($parts);
			return $userId;
		}
		return $this->userId;
	}

	public function getRichObjectString(): array {
		return [
			'type' => 'highlight',
			'id' => $this->getId(),
			'name' => $this->getType(),
		];
	}

	private function setMiscSettingsArray(array $value) : void {
		$this->setMiscSettings(json_encode($value));
	}

	private function getMiscSettingsArray() : ?array {
		return json_decode($this->getMiscSettings(), true);
	}

	/**
	 * @param bool|string|int|array $value
	 */
	private function setMiscSettingsByKey(string $key, $value): void {
		$miscSettings = $this->getMiscSettingsArray();
		$miscSettings[$key] = $value;
		$this->setMiscSettingsArray($miscSettings);
	}

	/**
	 * Returns the poll setting for the registration dialog option as default
	 * remove this later
	 * remove then OCA\Polls\Db\Poll::getPublicPollEmail() also
	 * @deprecated
	 */
	private function getDefaultPublicPollEmail() : string {
		try {
			return Container::queryPoll($this->getPollId())->getPublicPollEmail();
		} catch (\Exception $e) {
			return 'optional';
		}
	}
}
