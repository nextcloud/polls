<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

use DateTimeZone;
use JsonSerializable;
use OCA\Polls\Helper\Container;
use OCA\Polls\Helper\Hash;
use OCA\Polls\Model\Group\Circle;
use OCA\Polls\Model\Group\ContactGroup;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Model\User\Admin;
use OCA\Polls\Model\User\Contact;
use OCA\Polls\Model\User\Cron;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\Ghost;
use OCA\Polls\Model\User\User;
use OCA\Polls\UserSession;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IDateTimeZone;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\Share\IShare;

class UserBase implements JsonSerializable {
	/** @var string */
	public const TYPE = 'generic';
	/** @var string */
	public const TYPE_PUBLIC = 'public';
	/** @var string */
	public const TYPE_EXTERNAL = 'external';
	/** @var string */
	public const TYPE_EMPTY = 'empty';
	/** @var string */
	public const TYPE_GUEST = 'guest';
	/** @var string */
	public const TYPE_CIRCLE = Circle::TYPE;
	/** @var string */
	public const TYPE_CONTACT = Contact::TYPE;
	/** @var string */
	public const TYPE_CONTACTGROUP = ContactGroup::TYPE;
	/** @var string */
	public const TYPE_EMAIL = Email::TYPE;
	/** @var string */
	public const TYPE_GHOST = Ghost::TYPE;
	/** @var string */
	public const TYPE_GROUP = Group::TYPE;
	/** @var string */
	public const TYPE_USER = User::TYPE;
	/** @var string */
	public const TYPE_ADMIN = Admin::TYPE;
	/** @var string */
	public const TYPE_CRON = Cron::TYPE;

	/** @var string[] */
	protected array $categories = [];
	protected string $description = '';
	protected string $richObjectType = self::TYPE_USER;
	protected string $organisation = '';
	protected IDateTimeZone $timeZone;
	protected IGroupManager $groupManager;
	protected IL10N $l10n;
	protected UserSession $userSession;
	protected AppSettings $appSettings;

	public function __construct(
		protected string $id,
		protected string $type,
		protected string $displayName = '',
		protected string $emailAddress = '',
		protected string $languageCode = '',
		protected string $localeCode = '',
		protected string $timeZoneName = '',
	) {
		$this->l10n = Container::getL10N();
		$this->groupManager = Container::queryClass(IGroupManager::class);
		$this->timeZone = Container::queryClass(IDateTimeZone::class);
		$this->userSession = Container::queryClass(UserSession::class);
		$this->appSettings = Container::queryClass(AppSettings::class);
	}

	public function getId(): string {
		return $this->id;
	}

	public function getInternalUserId(): ?string {
		return null;
	}

	public function getShareUserId(): string {
		return $this->getId();
	}

	/**
	 * for later use
	 */
	public function getPrincipalUri(): string {
		return '';
	}

	/**
	 * hash the real userId to obfuscate the real userId
	 */
	public function getHashedUserId(): string {
		return Hash::getUserIdHash($this->id);
	}

	/**
	 * Returns the users' type
	 *
	 * Returned Type will be one of
	 * 		Contact::TYPE (Share),
	 * 		Email::TYPE (Share),
	 * 		Ghost::TYPE (deleted user),
	 * 		User::TYPE (NC User),
	 * 		Admin::TYPE (NC user with admin rights),
	 * 		Group::TYPE (NC Group),
	 * 		Circle::TYPE (Share),
	 * 		ContactGroup::TYPE (Share)
	 * 		Anon::TYPE (Anonymized User)
	 *
	 * @return string
	 **/
	public function getType(): string {
		return $this->type;
	}

	/**
	 * Returns the users' type used in shares
	 *
	 * Returned Type will be one of
	 * 		Email::TYPE (Share),
	 * 		Ghost::TYPE (deleted user),
	 * 		User::TYPE (NC User),
	 * 		Admin::TYPE (NC user with admin rights),
	 * 		Group::TYPE (NC Group),
	 * 		Circle::TYPE (Share),
	 * 		ContactGroup::TYPE (Share)
	 * 		Anon::TYPE (Anonymized User)
	 *
	 * @return string
	 **/
	public function getShareType(): string {
		return $this->type;
	}

	public function getIsGuest(): bool {
		return !in_array($this->type, [User::TYPE, Admin::TYPE]);
	}
	/**
	 * used for telling internal from guest users
	 */
	public function getSimpleType(): string {
		if (in_array($this->type, [User::TYPE, Admin::TYPE])) {
			return self::TYPE_USER;
		} elseif ($this->type === Ghost::TYPE) {
			return self::TYPE_GHOST;
		}

		return self::TYPE_GUEST;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getLanguageCodeIntl(): string {
		return str_replace('_', '-', $this->getLanguageCode());
	}

	public function getLocaleCode(): string {
		if (!$this->localeCode) {
			return $this->languageCode;
		}

		return $this->localeCode;
	}

	public function getLocaleCodeIntl(): string {
		return str_replace('_', '-', $this->getLocaleCode());
	}

	public function getTimeZone(): DateTimeZone {
		if ($this->timeZoneName) {
			return new DateTimeZone($this->timeZoneName);
		}
		return new DateTimeZone($this->timeZone->getTimeZone()->getName());
	}

	public function getTimeZoneName(): string {
		return $this->timeZoneName;
	}

	public function getDisplayName(): string {
		return $this->displayName;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getSubName(): string {
		return $this->getDescription();
	}

	public function getEmailAddress(): string {
		return $this->emailAddress;
	}

	public function getEmailAndDisplayName(): string {
		return $this->getDisplayName() . ' <' . $this->getEmailAddress() . '>';
	}

	public function getHasEmail(): bool {
		return boolVal($this->getEmailAddress());
	}

	/**
	 * return true, if the $checkname is equal to the userid or displayName (case insensitive)
	 */
	public function hasName(string $checkName): bool {
		return in_array(strtolower($checkName), [
			strtolower($this->getDisplayName()),
			strtolower($this->getId()),
		]);
	}

	/**
	 * @return string[]
	 *
	 * @psalm-return array<array-key, string>
	 */
	public function getCategories(): array {
		return $this->categories;
	}

	public function getIsNoUser(): bool {
		return $this->getSimpleType() !== self::TYPE_USER;
	}

	public function getRichObjectString() : array {
		return [
			'type' => $this->richObjectType,
			'id' => $this->getId(),
			'name' => $this->getDisplayName(),
		];
	}

	/**
	 * search all possible sharees - use ISearch to respect autocomplete restrictions
	 */
	public static function search(string $query = ''): array {
		$items = [];
		$types = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_EMAIL
		];
		if (Circle::isEnabled() && class_exists('\OCA\Circles\ShareByCircleProvider')) {
			$types[] = IShare::TYPE_CIRCLE;
		}

		[$result] = Container::queryClass(ISearch::class)->search($query, $types, false, 200, 0);

		foreach (($result['users'] ?? []) as $item) {
			$items[] = new User($item['value']['shareWith']);
		}

		foreach (($result['exact']['users'] ?? []) as $item) {
			$items[] = new User($item['value']['shareWith']);
		}

		foreach (($result['groups'] ?? []) as $item) {
			$items[] = new Group($item['value']['shareWith']);
		}

		foreach (($result['exact']['groups'] ?? []) as $item) {
			$items[] = new Group($item['value']['shareWith']);
		}

		$items = array_merge($items, Contact::search($query));
		$items = array_merge($items, ContactGroup::search($query));

		if (Circle::isEnabled()) {
			foreach (($result['circles'] ?? []) as $item) {
				$items[] = new Circle($item['value']['shareWith']);
			}
			foreach (($result['exact']['circles'] ?? []) as $item) {
				$items[] = new Circle($item['value']['shareWith']);
			}
		}

		return $items;
	}

	/**
	 * Default is an array with self as single element
	 * @return array
	 */
	public function getMembers(): array {
		return [$this];
	}

	/** @psalm-suppress PossiblyUnusedMethod */
	public function jsonSerialize(): array {
		if ($this->getIsCurrentUser()) {
			return $this->getRichUserArray();
		}
		return $this->getSimpleUserArray();
	}

	/**
	 * Full user array for poll owners, delegated poll admins and the current user himself
	 * without obfuscating/anonymizing
	 *
	 * @return (null|bool|string|string[])[]
	 */
	public function getRichUserArray(): array {
		return	[
			'array' => 'richArray',
			'categories' => $this->getCategories(),
			'desc' => $this->getDescription(),
			'displayName' => $this->getDisplayName(),
			'emailAddress' => $this->getEmailAddress(),
			'id' => $this->getId(),
			'user' => $this->getInternalUserId(),
			'isAdmin' => $this->getIsAdmin(),
			'isGuest' => $this->getIsGuest(),
			'isNoUser' => $this->getIsNoUser(),
			'isUnrestrictedOwner' => $this->getIsUnrestrictedPollOwner(),
			'languageCode' => $this->getLanguageCode(),
			'languageCodeIntl' => $this->getLanguageCodeIntl(),
			'localeCode' => $this->getLocaleCode(),
			'localeCodeIntl' => $this->getLocaleCodeIntl(),
			'organisation' => $this->getOrganisation(),
			'subname' => $this->getSubName(),
			'subtitle' => $this->getDescription(),
			'timeZone' => $this->getTimeZoneName(),
			'type' => $this->getType(),
			'userId' => $this->getId(),
		];
	}

	/**
	 * privacy and anonymizing section
	 */

	/**
	 * Simply user array returning safe attributes
	 *
	 * @return (null|bool|null|string)[]
	 */
	protected function getSimpleUserArray(): array {
		return	[
			'array' => 'simpleArray',
			'categories' => '',
			'desc' => '',
			'displayName' => $this->getSafeDisplayName(),
			'emailAddress' => $this->getSafeEmailAddress(),
			'id' => $this->getSafeId(),
			'user' => null,
			'isAdmin' => false,
			'isNoUser' => $this->getIsNoUser(),
			'isGuest' => $this->getIsGuest(),
			'isUnrestrictedOwner' => false,
			'languageCode' => '',
			'localeCode' => '',
			'organisation' => '',
			'subname' => '',
			'subtitle' => '',
			'timeZone' => '',
			'type' => $this->getSafeType(),
			'userId' => $this->getSafeId(),
		];
	}

	/**
	 * returns the safe id to avoid leaking the userId
	 */
	public function getSafeId(): string {
		// always return real userId for the current user
		if ($this->getIsCurrentUser()) {
			return $this->getId();
		}

		// hash the userId, if user is not logged in
		if (!$this->userSession->getIsLoggedIn()) {
			return $this->getHashedUserId();
		}

		// otherwise return the obfuscated userId
		return $this->getId();
	}

	/**
	 * anonymize the displayname in case of anonymous settings
	 */
	public function getSafeDisplayName(): string {
		return $this->displayName;
	}

	// Function for obfuscating mail adresses; Default return the email address
	public function getSafeEmailAddress(): string {
		// always return real email address if this user object is the current user
		if ($this->getIsCurrentUser()) {
			return $this->getEmailAddress();
		}

		// hide email address, if user is denied to see email addresses (App setting)
		// hide email address, if user is not logged in
		if (!$this->appSettings->getAllowSeeMailAddresses() || !$this->userSession->getIsLoggedIn()) {
			return '';
		}

		return $this->getEmailAddress();
	}

	public function getOrganisation(): string {
		return $this->organisation;
	}

	public function getIsCurrentUser(): bool {
		return $this->getId() === $this->userSession->getCurrentUserId();
	}

	/**
	 * returns true, if the user is an unrestricted owner
	 * Only valid for User
	 */
	public function getIsUnrestrictedPollOwner(): bool {
		return false;
	}

	/**
	 * returns true, if the user is an admin
	 * Only valid for User, false for other user types
	 */
	public function getIsAdmin(): bool {
		return false;
	}

	// TODO: reactivate this function later
	/** @psalm-suppress PossiblyUnusedMethod */
	public function getIsSystemUser(): bool {
		return $this->groupManager->isAdmin($this->getId());
	}

	/**
	 * returns true, if the user is member of the requested group
	 * Only valid for User, false for other user types
	 */
	public function getIsInGroup(string $groupName): bool {
		return false;
	}

	/**
	 * returns true, if the user is member of one of the requested groups
	 * Only valid for User, false for other user types
	 */
	public function getIsInGroupArray(array $groupNames): bool {
		return false;
	}

	/**
	 * returns the safe id to avoid leaking the real user type
	 */
	public function getSafeType(): string {
		// always return real type for the current user
		if ($this->getIsCurrentUser()) {
			return $this->getType();
		}

		// return simple type, if user is not logged in
		if (!$this->userSession->getIsLoggedIn()) {
			return $this->getSimpleType();
		}

		return $this->getType();
	}


}
