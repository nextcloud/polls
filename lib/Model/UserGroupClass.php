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

namespace OCA\Polls\Model;

use OCA\Polls\Exceptions\InvalidShareTypeException;

use OCP\Collaboration\Collaborators\ISearch;
use OCP\Share\IShare;
use OCA\Polls\AppInfo\Application;

class UserGroupClass implements \JsonSerializable {
	public const TYPE = 'generic';
	public const TYPE_PUBLIC = 'public';
	public const TYPE_EXTERNAL = 'external';
	public const TYPE_CIRCLE = Circle::TYPE;
	public const TYPE_CONTACT = Contact::TYPE;
	public const TYPE_CONTACTGROUP = ContactGroup::TYPE;
	public const TYPE_EMAIL = Email::TYPE;
	public const TYPE_GROUP = Group::TYPE;
	public const TYPE_USER = User::TYPE;

	private $l10n;

	/** @var string */
	protected $id;

	/** @var string */
	protected $type;

	/** @var string */
	protected $displayName = '';

	/** @var string */
	protected $description = '';

	/** @var string */
	protected $emailAddress = '';

	/** @var string */
	protected $language = '';

	/** @var string */
	protected $organisation = '';

	/** @var string */
	protected $icon = '';

	/** @var bool */
	protected $isNoUser = true;

	/** @var string[] */
	protected $categories = [];

	public function __construct(
		$id,
		$type,
		$displayName = '',
		$emailAddress = '',
		$language = ''
	) {
		$this->id = $id;
		$this->type = $type;
		$this->displayName = $displayName;
		$this->emailAddress = $emailAddress;
		$this->language = $language;
		$this->icon = 'icon-share';
		$this->l10n = \OC::$server->getL10N('polls');
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
		return $this->emailAddress ? $this->emailAddress : '';
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

	public function setOrganisation($organisation): string {
		$this->organisation = $organisation;
		return $this->organisation;
	}

	/**
	 * serach all possible sharees - use ISearch to respect autocomplete restrictions
	 *
	 * Undocumented function long description
	 *
	 * @param type var Description
	 * @return return type
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

		list($result, $more) = self::getContainer()->query(ISearch::class)->search($query, $types, false, 200, 0);

		foreach ($result['users'] as $item) {
			$items[] = new User($item['value']['shareWith']);
		}

		foreach ($result['exact']['users'] as $item) {
			$items[] = new User($item['value']['shareWith']);
		}

		foreach ($result['groups'] as $item) {
			$items[] = new Group($item['value']['shareWith']);
		}

		foreach ($result['exact']['groups'] as $item) {
			$items[] = new Group($item['value']['shareWith']);
		}

		$items = array_merge($items, Contact::search($query));
		$items = array_merge($items, ContactGroup::search($query));

		if (Circle::isEnabled()) {
			foreach ($result['circles'] as $item) {
				$items[] = new Circle($item['value']['shareWith']);
			}
			foreach ($result['exact']['circles'] as $item) {
				$items[] = new Circle($item['value']['shareWith']);
			}
		}


		return $items;
	}

	/**
	 * @return array
	 */
	public function getMembers() {
		return [];
	}

	protected static function getContainer() {
		$app = \OC::$server->query(Application::class);

		return $app->getContainer();
	}

	/**
	 * @return Circle|Contact|ContactGroup|Email|GenericUser|Group|User
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
			'id'        	=> $this->getId(),
			'user'          => $this->getId(),
			'userId'        => $this->getId(),
			'type'       	=> $this->getType(),
			'displayName'	=> $this->getDisplayName(),
			'organisation'	=> $this->getOrganisation(),
			'emailAddress'	=> $this->getEmailAddress(),
			'language'		=> $this->getLanguage(),
			'desc' 			=> $this->getDescription(),
			'subtitle'		=> $this->getDescription(),
			'icon'			=> $this->getIcon(),
			'categories'	=> $this->getCategories(),
			'isNoUser'		=> $this->getIsNoUser(),
		];
	}
}
