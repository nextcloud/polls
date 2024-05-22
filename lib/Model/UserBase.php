<?php

declare(strict_types=1);
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

	/** @var string[] */
	protected array $categories = [];
	protected string $anonymizeLevel = EntityWithUser::ANON_PRIVACY;
	protected string $description = '';
	protected string $richObjectType = 'user';
	protected string $organisation = '';
	protected string $icon = '';
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
		$this->icon = 'icon-share';
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
	public function getPrincipalUri(): ?string {
		return null;
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

	/**
	 * @deprecated Not used anymore?
	 */
	private function getIcon(): string {
		return $this->icon;
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

		[$result, $more] = Container::queryClass(ISearch::class)->search($query, $types, false, 200, 0);

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

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
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
	 *
	 * @psalm-return array{userId: string, displayName: string, emailAddress: string, subName: string, subtitle: string, isNoUser: bool, desc: string, type: string, id: string, user: string, organisation: string, languageCode: string, localeCode: string, timeZone: string, icon: string, categories: array<string>}
	 */
	public function getRichUserArray(): array {
		return	[
			'userId' => $this->getId(),
			'displayName' => $this->getDisplayName(),
			'emailAddress' => $this->getEmailAddress(),
			'subName' => $this->getSubName(),
			'subtitle' => $this->getDescription(),
			'isNoUser' => $this->getIsNoUser(),
			'desc' => $this->getDescription(),
			'type' => $this->getType(),
			'id' => $this->getId(),
			'user' => $this->getId(),
			'organisation' => $this->getOrganisation(),
			'languageCode' => $this->getLanguageCode(),
			'localeCode' => $this->getLocaleCode(),
			'timeZone' => $this->getTimeZoneName(),
			'icon' => $this->getIcon(),
			'categories' => $this->getCategories(),
		];
	}

	/**
	 * privacy and anonymizing section
	 */

	/**
	 * Simply user array returning safe attributes
	 * @return (bool|string)[]
	 *
	 * @psalm-return array{id: string, userId: string, displayName: string, emailAddress: string, isNoUser: bool, type: string}
	 */
	protected function getSimpleUserArray(): array {
		return	[
			'id' => $this->getSafeId(),
			'userId' => $this->getSafeId(),
			'displayName' => $this->getSafeDisplayName(),
			'emailAddress' => $this->getSafeEmailAddress(),
			'isNoUser' => $this->getIsNoUser(),
			'type' => $this->getSafeType(),
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
	public function getSafeEmailAddress(): string {
		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL) {
			return '';
		}

		if ($this->appSettings->getAllowSeeMailAddresses()) {
			return $this->getEmailAddress();
		}

		return '';
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

	public function getIsInGroup(string $groupName): bool {
		return $this->groupManager->isInGroup($this->getId(), $groupName);
	}

	/**
	 * returns the safe id to avoid leaking thereal user type
	 */
	public function getSafeType(): string {
		// always return real userId for the current user
		if ($this->getIsCurrentUser()) {
			return $this->getType();
		}

		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL) {
			return self::TYPE_ANON;
		}

		return $this->getType();
	}


}
