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

namespace OCA\Polls\Model\UserGroup;

use OCA\Polls\Exceptions\InvalidShareTypeException;

use DateTimeZone;
use OCP\IL10N;
use OCA\Polls\Helper\Container;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\Share\IShare;
use OCP\IDateTimeZone;

class UserBase implements \JsonSerializable {
	public const TYPE = 'generic';
	public const TYPE_PUBLIC = 'public';
	public const TYPE_EXTERNAL = 'external';
	public const TYPE_CIRCLE = Circle::TYPE;
	public const TYPE_CONTACT = Contact::TYPE;
	public const TYPE_CONTACTGROUP = ContactGroup::TYPE;
	public const TYPE_EMAIL = Email::TYPE;
	public const TYPE_GROUP = Group::TYPE;
	public const TYPE_USER = User::TYPE;
	public const TYPE_ADMIN = Admin::TYPE;

	/** @var string */
	protected $richObjectType = 'user';

	/** @var IL10N */
	private $l10n;

	/** @var string */
	protected $id;

	/** @var string */
	protected $type;

	/** @var string */
	protected $displayName = '';

	/** @var string */
	protected $description = '';

	/** @var string|null */
	protected $emailAddress = null;

	/** @var string */
	protected $language = '';

	/** @var string */
	protected $locale = '';

	/** @var string */
	protected $organisation = '';

	/** @var string */
	protected $icon = '';

	/** @var bool */
	protected $isNoUser = true;

	/** @var string[] */
	protected $categories = [];

	/** @var IDateTimeZone */
	protected $timezone;

	public function __construct(
		string $id,
		string $type,
		string $displayName = '',
		string $emailAddress = '',
		string $language = '',
		string $locale = ''
	) {
		$this->id = $id;
		$this->type = $type;
		$this->displayName = $displayName;
		$this->emailAddress = $emailAddress;
		$this->language = $language;
		$this->locale = $locale;
		$this->icon = 'icon-share';
		$this->l10n = Container::getL10N();
		$this->timezone = Container::queryClass(IDateTimeZone::class);
	}

	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getPublicId(): string {
		return $this->id;
	}

	public function getUser(): string {
		return $this->id;
	}

	public function getType(): string {
		return $this->type;
	}

	public function getLanguage(): string {
		return $this->language;
	}

	public function getTimeZone(): DateTimeZone {
		return new DateTimeZone($this->timezone->getTimeZone()->getName());
	}

	public function getLocale(): string {
		return $this->locale;
	}

	public function getDisplayName(): string {
		return $this->displayName;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getIcon(): string {
		return $this->icon;
	}

	public function getEmailAddress(): string {
		return $this->emailAddress ?? '';
	}

	public function getOrganisation(): string {
		return $this->organisation;
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

	public function setLanguage(string $language): string {
		$this->language = $language;
		return $this->language;
	}

	public function setLocale(string $locale): string {
		$this->locale = $locale;
		return $this->locale;
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
			IShare::TYPE_GROUP
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

	/**
	 * @return Circle|Contact|ContactGroup|Email|GenericUser|Group|User|Admin
	 */
	public static function getUserGroupChild(string $type, string $id, string $displayName = '', string $emailAddress = '') {
		switch ($type) {
			case Group::TYPE:
				return new Group($id);
			case Circle::TYPE:
				return new Circle($id);
			case Contact::TYPE:
				return new Contact($id);
			case ContactGroup::TYPE:
				return new ContactGroup($id);
			case User::TYPE:
				return new User($id);
			case Admin::TYPE:
				return new Admin($id);
			case Email::TYPE:
				return new Email($id);
			case self::TYPE_PUBLIC:
				return new GenericUser($id, self::TYPE_PUBLIC);
			case self::TYPE_EXTERNAL:
				return new GenericUser($id, self::TYPE_EXTERNAL, $displayName, $emailAddress);
			default:
				throw new InvalidShareTypeException('Invalid share type (' . $type . ')');
			}
	}

	public function jsonSerialize(): array {
		return	[
			'id' => $this->getId(),
			'user' => $this->getId(),
			'userId' => $this->getId(),
			'type' => $this->getType(),
			'displayName' => $this->getDisplayName(),
			'organisation' => $this->getOrganisation(),
			'emailAddress' => $this->getEmailAddress(),
			'language' => $this->getLanguage(),
			'desc' => $this->getDescription(),
			'subtitle' => $this->getDescription(),
			'icon' => $this->getIcon(),
			'categories' => $this->getCategories(),
			'isNoUser' => $this->getIsNoUser(),
		];
	}
}
