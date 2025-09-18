<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use JsonSerializable;
use OCA\Polls\AppConstants;
use OCA\Polls\Helper\Container;
use OCP\IURLGenerator;

/**
 * @psalm-suppress UnusedProperty
 * @method int getId()
 * @method void setId(int $value)
 * @method string getToken()
 * @method void setToken(string $value)
 * @method string getType()
 * @method void setType(string $value)
 * @method ?int getPollId()
 * @method void setPollId(?int $value)
 * @method ?int getGroupId()
 * @method void setGroupId(?int $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method string getEmailAddress()
 * @method void setEmailAddress(string $value)
 * @method int getInvitationSent()
 * @method void setInvitationSent(int $value)
 * @method int getReminderSent()
 * @method void setReminderSent(int $value)
 * @method int getLocked()
 * @method void setLocked(int $value)
 * @method string getDisplayName()
 * @method void setDisplayName(string $value)
 * @method string getMiscSettings()
 * @method void setMiscSettings(string $value)
 * @method ?int getAnonymizedVotes()
 * @method int getDeleted()
 * @method void setDeleted(int $value)
 */
class Share extends EntityWithUser implements JsonSerializable {
	/** @var string */
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


	public const CONVERATABLE_PUBLIC_SHARES = [
		self::TYPE_EMAIL,
		self::TYPE_CONTACT,
	];

	// Share types, that are allowed for public access (without login)
	public const SHARE_PUBLIC_ACCESS_ALLOWED = [
		self::TYPE_PUBLIC,
		self::TYPE_EXTERNAL,
		...self::CONVERATABLE_PUBLIC_SHARES,
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

	public const GROUP_SHARES = [
		self::TYPE_USER,
		self::TYPE_GROUP,
		self::TYPE_ADMIN,
	];

	protected IURLGenerator $urlGenerator;

	// schema columns
	public $id = null;
	protected ?int $pollId = null;
	protected ?int $groupId = null;
	protected string $token = '';
	protected string $type = '';
	protected ?string $label = null;
	protected string $userId = '';
	protected ?string $displayName = null;
	protected ?string $emailAddress = null;
	protected int $invitationSent = 0;
	protected int $reminderSent = 0;
	protected int $locked = 0;
	protected ?string $miscSettings = null;
	protected int $deleted = 0;

	// joined columns
	protected int $voted = 0;
	protected ?int $anonymizedVotes = 0;

	public function __construct() {
		$this->addType('pollId', 'integer');
		$this->addType('invitationSent', 'integer');
		$this->addType('locked', 'integer');
		$this->addType('reminderSent', 'integer');
		$this->addType('deleted', 'integer');
		$this->urlGenerator = Container::queryClass(IURLGenerator::class);
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
			'groupId' => $this->getGroupId(),
			'invitationSent' => boolval($this->getInvitationSent()),
			'reminderSent' => boolval($this->getReminderSent()),
			'locked' => boolval($this->getDeleted() ? 0 : $this->getLocked()),
			'label' => $this->getLabel(),
			'URL' => $this->getURL(),
			'publicPollEmail' => $this->getPublicPollEmail(),
			'voted' => boolval($this->getVoted()),
			'deleted' => boolval($this->getDeleted()),
			'user' => $this->getUser()->getRichUserArray(),
		];
	}

	private function getVoted(): int {
		return $this->getAnonymizedVotes() ? 0 : $this->voted;
	}

	/**
	 * Setting, if email is optional, mandatory or hidden on public poll registration
	 */
	public function getPublicPollEmail(): string {
		return $this->getMiscSettingsArray()['publicPollEmail'] ?? 'optional';
	}

	/**
	 * Get userId of share user
	 */
	public function getUserId(): string {
		return $this->userId;
	}

	/**
	 * Get userId of share user
	 */
	public function getType(): string {
		if (($this->getDeleted() || $this->getLocked()) && $this->type === self::TYPE_ADMIN) {
			return self::TYPE_USER;
		}
		return $this->type;
	}

	/**
	 * Setting, if email is optional, mandatory or hidden on public poll registration
	 */
	public function setPublicPollEmail(string $value): void {
		$this->setMiscSettingsByKey('publicPollEmail', $value);
	}

	/**
	 * Share label for public shares, now stored as displayName
	 * TODO: remove after migration was introduced
	 */
	public function setLabel(string $label): void {
		$this->setDisplayName($label);
		$this->label = $label;
	}

	/**
	 * Share label for public shares
	 * TODO: remove after migrating labels to displayName
	 */
	public function getLabel(): string {
		// In case of public poll use label as fallback for displayName
		return $this->displayName ?? $this->label ?? '';
	}

	/**
	 * Sharee's displayName. In case of public poll label is used instead
	 * TODO: remove public poll check after migration labels to displayName
	 */
	public function getDisplayName(): string {
		if ($this->getType() === self::TYPE_PUBLIC) {
			return $this->getLabel();
		}
		return $this->displayName ?? '';
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
		if (!$this->getPollId()) {
			return '';
		}

		if (in_array($this->type, [self::TYPE_USER, self::TYPE_ADMIN, self::TYPE_GROUP], true)) {
			return $this->urlGenerator->linkToRouteAbsolute(
				AppConstants::APP_ID . '.page.vote',
				['id' => $this->pollId]
			);
		} elseif ($this->token) {
			return $this->urlGenerator->linkToRouteAbsolute(
				AppConstants::APP_ID . '.public.votePage',
				['token' => $this->token]
			);
		} else {
			return '';
		}
	}

	public function getRichObjectString(): array {
		return [
			'type' => 'highlight',
			'id' => (string)$this->getId(),
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
