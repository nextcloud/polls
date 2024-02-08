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
use OCA\Polls\Db\EntityWithUser;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\Group\Circle;
use OCA\Polls\Model\Group\ContactGroup;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\User\Admin;
use OCA\Polls\Model\User\Contact;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\Ghost;
use OCA\Polls\Model\User\User;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IDateTimeZone;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\Share\IShare;

class UserBase implements \JsonSerializable {
	/** @var string */
	public const TYPE = 'generic';
	/** @var string */
	public const TYPE_PUBLIC = 'public';
	/** @var string */
	public const TYPE_EXTERNAL = 'external';
	public const TYPE_EMPTY = 'empty';
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
	protected bool $isNoUser = true;
	protected string $description = '';
	protected string $richObjectType = 'user';
	protected string $organisation = '';
	protected string $icon = '';
	protected IDateTimeZone $timeZone;
	protected IGroupManager $groupManager;
	protected IL10N $l10n;
	protected IUserSession $userSession;
	protected UserMapper $userMapper;

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
		$this->groupManager = Container::queryClass(IGroupManager::class);
		$this->timeZone = Container::queryClass(IDateTimeZone::class);
		$this->userMapper = Container::queryClass(UserMapper::class);
		$this->userSession = Container::queryClass(IUserSession::class);
	}

	public function getId(): string {
		return $this->id;
	}

	public function getShareUserId(): string {
		return $this->getId();
	}

	public function setAnonymized(string $anonymizeLevel = EntityWithUser::ANON_PRIVACY): void {
		$this->anonymizeLevel = $anonymizeLevel;
	}

	public function getSafeId(): string {
		if ($this->getId() === $this->userMapper->getCurrentUserCached()->getId()) {
			return $this->getId();
		}

		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL) {
			return $this->getHashedUserId();
		}

		if ($this->userMapper->getCurrentUserCached()->getIsLoggedIn()) {
			return $this->getId();
		}

		return $this->getHashedUserId();
	}

	public function getPrincipalUri(): ?string {
		return null;
	}

	public function getHashedUserId(?string $name = null): string {
		if ($name) {
			return hash('md5', $name);
		}
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

	public function getsafeDisplayName(): string {
		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL) {
			return 'Anon';
		}
		return $this->displayName;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getIcon(): string {
		return $this->icon;
	}

	public function getEmailAddress(): string {
		return $this->emailAddress;
	}

	public function getEmailAndDisplayName(): string {
		return $this->getDisplayName() . ' <' . $this->getEmailAddress() . '>';
	}

	// Function for obfuscating mail adresses; Default return the email address
	public function getEmailAddressMasked(): string {
		return $this->emailAddress;
	}

	// Function for obfuscating mail adresses; Default return the email address
	public function getEmailAddressSafe(): string {
		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL) {
			return '';
		}

		return $this->getEmailAddressMasked();
	}
	public function getOrganisation(): string {
		return $this->organisation;
	}

	public function getIsLoggedIn(): bool {
		return $this->userSession->isLoggedIn();
	}

	public function getIsAdmin(): bool {
		return $this->groupManager->isAdmin($this->id);
	}

	public function getIsInGroup(string $groupName): bool {
		return $this->groupManager->isInGroup($this->id, $groupName);
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
		if ($this->anonymizeLevel === EntityWithUser::ANON_FULL && $this->userMapper->getCurrentUser()->getId() !== $this->getId()) {
			return true;
		}
		return $this->isNoUser;
	}

	public function setType(string $type): string {
		$this->type = $type;
		return $this->type;
	}

	public function setDisplayName(string $displayName): string {
		$this->displayName = $displayName;
		return $this->displayName;
	}

	public function setDescription(string $description): string {
		$this->description = $description;
		return $this->description;
	}

	public function setEmailAddress(string $emailAddress) : string {
		$this->emailAddress = $emailAddress;
		return $this->emailAddress;
	}

	public function setLanguageCode(string $languageCode): string {
		$this->languageCode = $languageCode;
		return $this->languageCode;
	}

	public function setLocaleCode(string $localeCode): string {
		$this->localeCode = $localeCode;
		return $this->localeCode;
	}

	public function setOrganisation(string $organisation): string {
		$this->organisation = $organisation;
		return $this->organisation;
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
	 * Default is array with self as single element
	 * @return array
	 */
	public function getMembers(): array {
		return [$this];
	}

	public function jsonSerialize(): array {
		if ($this->getId() === $this->userMapper->getCurrentUserCached()->getId()) {
			return $this->getRichUserArray();
		}
		return $this->getSimpleUserArray();
	}

	/**
	 * @return (bool|string|string[])[]
	 *
	 * @psalm-return array{userId: string, displayName: string, emailAddress: string, isNoUser: bool, type: string, id: string, user: string, organisation: string, languageCode: string, localeCode: string, timeZone: string, desc: string, subname: string, subtitle: string, icon: string, categories: array<string>}
	 */
	public function getRichUserArray(): array {
		return	[
			'userId' => $this->getId(),
			'displayName' => $this->getDisplayName(),
			'emailAddress' => $this->getEmailAddressMasked(),
			'isNoUser' => $this->getIsNoUser(),
			'type' => $this->getType(),
			'id' => $this->getId(),
			'user' => $this->getId(),
			'organisation' => $this->getOrganisation(),
			'languageCode' => $this->getLanguageCode(),
			'localeCode' => $this->getLocaleCode(),
			'timeZone' => $this->getTimeZoneName(),
			'desc' => $this->getDescription(),
			'subname' => $this->getDescription(),
			'subtitle' => $this->getDescription(),
			'icon' => $this->getIcon(),
			'categories' => $this->getCategories(),
		];
	}

	/**
	 * @return (bool|string)[]
	 *
	 * @psalm-return array{id: string, userId: string, displayName: string, emailAddress: string, isNoUser: bool, type: string}
	 */
	private function getSimpleUserArray(): array {
		return	[
			'id' => $this->getSafeId(),
			'userId' => $this->getSafeId(),
			'displayName' => $this->getSafeDisplayName(),
			'emailAddress' => $this->getEmailAddressSafe(),
			'isNoUser' => $this->getIsNoUser(),
			'type' => $this->getType(),
		];
	}
}
