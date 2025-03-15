<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

use DateTimeZone;
use JsonSerializable;
use OCA\Polls\Db\EntityWithUser;
use OCA\Polls\Helper\Container;
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
use OCP\Server;
use OCP\Share\IShare;

class UserBase implements JsonSerializable {
	/** @var string */
	public const TYPE = 'generic';
	/** @var string */
	public const TYPE_PUBLIC = 'public';
	/** @var string */
	public const TYPE_EXTERNAL = 'external';
	public const TYPE_EMPTY = 'empty';
	public const TYPE_ANON = 'anonymous';
	public const TYPE_CIRCLE = Circle::TYPE;
	public const TYPE_CONTACT = Contact::TYPE;
	public const TYPE_CONTACTGROUP = ContactGroup::TYPE;
	public const TYPE_EMAIL = Email::TYPE;
	public const TYPE_GHOST = Ghost::TYPE;
	public const TYPE_GROUP = Group::TYPE;
	public const TYPE_USER = User::TYPE;
	public const TYPE_ADMIN = Admin::TYPE;
	public const TYPE_CRON = Cron::TYPE;

	/** @var string[] */
	protected array $categories = [];
	protected string $anonymizeLevel = EntityWithUser::ANON_PRIVACY;
	protected string $description = '';
	protected string $richObjectType = 'user';
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
		$this->groupManager = Server::get(IGroupManager::class);
		$this->timeZone = Server::get(IDateTimeZone::class);
		$this->userSession = Server::get(UserSession::class);
		$this->appSettings = Server::get(AppSettings::class);
	}

	public function getId(): string {
		return $this->id;
	}

	public function getShareUserId(): string {
		return $this->getId();
	}

	public function setAnonymizeLevel(string $anonymizeLevel = EntityWithUser::ANON_PRIVACY): void {
		$this->anonymizeLevel = $anonymizeLevel;
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
		// TODO: add a session salt
		return hash('md5', $this->getId());
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
	 *
	 * @return string
	 **/
	public function getShareType(): string {
		return $this->type;
	}

	/**
	 * used for telling internal from guest users
	 */
	public function getSimpleType(): string {
		return in_array($this->type, [User::TYPE, Admin::TYPE]) ? 'user' : 'guest';
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getLocaleCode(): string {
		if (!$this->localeCode) {
			return $this->languageCode;
		}

		return $this->localeCode;
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
			strtolower($this->getDisplayName()), strtolower($this->getId()),
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
		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL && $this->getIsCurrentUser()) {
			return true;
		}
		return $this->getSimpleType() !== 'user';
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
	 * @return (bool|string|string[])[]
	 */
	public function getRichUserArray(): array {
		return	[
			'id' => $this->getId(),
			'userId' => $this->getId(),
			'displayName' => $this->getDisplayName(),
			'emailAddress' => $this->getEmailAddress(),
			'subname' => $this->getSubName(),
			'subtitle' => $this->getDescription(),
			'isNoUser' => $this->getIsNoUser(),
			'desc' => $this->getDescription(),
			'type' => $this->getType(),
			'organisation' => $this->getOrganisation(),
			'languageCode' => $this->getLanguageCode(),
			'localeCode' => $this->getLocaleCode(),
			'timeZone' => $this->getTimeZoneName(),
			'categories' => $this->getCategories(),
		];
	}

	/**
	 * privacy and anonymizing section
	 */

	/**
	 * Simply user array returning safe attributes
	 *
	 * @return (bool|null|string)[]
	 */
	protected function getSimpleUserArray(): array {
		return	[
			'id' => $this->getSafeId(),
			'userId' => $this->getSafeId(),
			'displayName' => $this->getSafeDisplayName(),
			'emailAddress' => $this->getSafeEmailAddress(),
			'subname' => '',
			'subtitle' => '',
			'isNoUser' => $this->getIsNoUser(),
			'desc' => '',
			'type' => $this->getSafeType(),
			'organisation' => '',
			'languageCode' => '',
			'localeCode' => '',
			'timeZone' => '',
			'categories' => '',
		];
	}

	/**
	 * returns the safe id to avoid leaking the userId
	 */
	public function getSafeId(): string {
		// return real userId for cron jobs
		if ($this->userSession->getUser()->getIsSystemUser()) {
			return $this->getId();
		}

		// always return real userId for the current user
		if ($this->getIsCurrentUser()) {
			return $this->getId();
		}

		// return hashed userId, if fully anonimized
		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL) {
			return $this->getHashedUserId();
		}

		// internal users may see the real userId
		if ($this->userSession->getIsLoggedIn()) {
			return $this->getId();
		}

		// otherwise return the obfuscated userId
		return $this->getHashedUserId();
	}

	/**
	 * anonymize the displayname in case of anonymous settings
	 */
	public function getSafeDisplayName(): string {
		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL) {
			return 'Anon';
		}

		return $this->displayName;
	}

	// Function for obfuscating mail adresses; Default return the email address
	public function getSafeEmailAddress(): ?string {
		// return real email address for cron jobs
		if ($this->userSession->getUser()->getIsSystemUser()) {
			return $this->getEmailAddress();
		}

		// always return real email address for the current user
		if ($this->getIsCurrentUser()) {
			return $this->getEmailAddress();
		}

		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL) {
			return null;
		}

		if ($this->appSettings->getAllowSeeMailAddresses()) {
			return $this->getEmailAddress();
		}

		return null;
	}

	public function getOrganisation(): string {
		return $this->organisation;
	}

	public function getIsCurrentUser(): bool {
		return $this->getId() === $this->userSession->getCurrentUserId();
	}

	public function getIsAdmin(): bool {
		return $this->groupManager->isAdmin($this->getId());
	}

	public function getIsSystemUser(): bool {
		return $this->groupManager->isAdmin($this->getId());
	}

	public function getIsInGroup(string $groupName): bool {
		return $this->groupManager->isInGroup($this->getId(), $groupName);
	}

	public function getIsInGroupArray(array $groupNames): bool {
		if (!($this instanceof User)) {
			return false;
		}

		foreach ($groupNames as $groupName) {
			if ($this->getIsInGroup($groupName)) {
				return true;
			}
		}
		return false;
	}


	/**
	 * returns the safe id to avoid leaking the real user type
	 */
	public function getSafeType(): string {
		// return real type for cron jobs
		if ($this->userSession->getUser()->getIsSystemUser()) {
			return $this->getType();
		}

		// always return real type for the current user
		if ($this->getIsCurrentUser()) {
			return $this->getType();
		}

		// return hashed userId, if fully anonimized
		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL) {
			return self::TYPE_ANON;
		}

		return $this->getType();
	}


}
