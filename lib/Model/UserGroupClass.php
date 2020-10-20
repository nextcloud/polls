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

class UserGroupClass implements \JsonSerializable {
	public const TYPE = 'generic';
	public const TYPE_PUBLIC = 'public';
	public const TYPE_EXTERNAL = 'external';

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

	/** @var boolean */
	protected $isNoUser = true;

	/** @var string[] */
	protected $categories = [];

	/**
	 * User constructor.
	 * @param $id
	 * @param $displayName
	 */
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

	/**
	 * getId
	 * @NoAdminRequired
	 * @return String
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * getUser
	 * @NoAdminRequired
	 * @return String
	 */
	public function getUser() {
		return $this->id;
	}

	/**
	 * getType
	 * @NoAdminRequired
	 * @return String
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * getLanguage
	 * @NoAdminRequired
	 * @return String
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * getDisplayName
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDisplayName() {
		return $this->displayName;
	}

	/**
	 * getDescription
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * getIcon
	 * @NoAdminRequired
	 * @return String
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * getEmailAddress
	 * @NoAdminRequired
	 * @return String
	 */
	public function getEmailAddress() {
		return $this->emailAddress;
	}

	/**
	 * getOrganisation
	 * @NoAdminRequired
	 * @return String
	 */
	public function getOrganisation() {
		return $this->organisation;
	}

	/**
	 * getCategories
	 * @NoAdminRequired
	 * @return Array
	 */
	public function getCategories() {
		return $this->categories;
	}

	/**
	 * getOrganisation
	 * @NoAdminRequired
	 * @return String
	 */
	public function getIsNoUser() {
		return $this->isNoUser;
	}

	/**
	 * setType
	 * @NoAdminRequired
	 * @param string $type
	 * @return String
	 */
	public function setType($type) {
		$this->type = $type;
		return $this->type;
	}

	/**
	 * setDisplayName
	 * @NoAdminRequired
	 * @param string $displayName
	 * @return String
	 */
	public function setDisplayName($displayName) {
		$this->displayName = $displayName;
		return $this->displayName;
	}

	/**
	 * setDescription
	 * @NoAdminRequired
	 * @param string $description
	 * @return String
	 */
	public function setDescription($description) {
		$this->description = $description;
		return $this->description;
	}

	/**
	 * setEmailAddress
	 * @NoAdminRequired
	 * @param string $emailAddress
	 * @return String
	 */
	public function setEmailAddress($emailAddress) {
		$this->emailAddress = $emailAddress;
		return $this->emailAddress;
	}

	/**
	 * setLanguage
	 * @NoAdminRequired
	 * @param string $language
	 * @return String
	 */
	public function setLanguage($language) {
		$this->language = $language;
		return $this->language;
	}

	/**
	 * setOrganisation
	 * @NoAdminRequired
	 * @param string $organisation
	 * @return String
	 */
	public function setOrganisation($organisation) {
		$this->organisation = $organisation;
		return $this->organisation;
	}

	/**
	 * search
	 * @NoAdminRequired
	 * @return Array
	 */
	public static function search() {
		return [];
	}

	public static function getUserGroupChild($type, $id, $displayName = '', $emailAddress = '') {
		switch ($type) {
			case Group::TYPE:
				return new Group($id);
				break;
			case Circle::TYPE:
				return new Circle($id);
				break;
			case Contact::TYPE:
				return new Contact($id);
				break;
			case ContactGroup::TYPE:
				return new ContactGroup($id);
				break;
			case User::TYPE:
				return new User($id);
				break;
			case Email::TYPE:
				return new Email($id);
				break;
			case self::TYPE_PUBLIC:
				return new GenericUser($id,self::TYPE_PUBLIC);
				break;
			case self::TYPE_EXTERNAL:
				return new GenericUser($id, self::TYPE_EXTERNAL, $displayName, $emailAddress);
				break;
			default:
				throw new InvalidShareType('Invalid share type (' . $type . ')');
			}
	}

	/**
	 * @return array
	 */
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
			'icon'			=> $this->getIcon(),
			'categories'	=> $this->getCategories(),
			'isNoUser'		=> $this->getIsNoUser(),
		];
	}
}
