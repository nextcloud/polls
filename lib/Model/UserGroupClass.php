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
	 * @return String
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * getPublicId
	 * @return String
	 */
	public function getPublicId() {
		return $this->id;
	}

	/**
	 * getUser
	 * @return String
	 */
	public function getUser() {
		return $this->id;
	}

	/**
	 * getType
	 * @return String
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * getLanguage
	 * @return String
	 */
	public function getLanguage() {
		return $this->language;
	}

	/**
	 * getDisplayName
	 * @return String
	 */
	public function getDisplayName() {
		return $this->displayName;
	}

	/**
	 * getDescription
	 * @return String
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * getIcon
	 * @return String
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * getEmailAddress
	 * @return String
	 */
	public function getEmailAddress() {
		return $this->emailAddress;
	}

	/**
	 * getOrganisation
	 * @return String
	 */
	public function getOrganisation() {
		return $this->organisation;
	}

	/**
	 * getCategories
	 * @return Array
	 */
	public function getCategories() {
		return $this->categories;
	}

	/**
	 * getOrganisation
	 * @return String
	 */
	public function getIsNoUser() {
		return $this->isNoUser;
	}

	/**
	 * setType
	 * @param string $type
	 * @return String
	 */
	public function setType($type) {
		$this->type = $type;
		return $this->type;
	}

	/**
	 * setDisplayName
	 * @param string $displayName
	 * @return String
	 */
	public function setDisplayName($displayName) {
		$this->displayName = $displayName;
		return $this->displayName;
	}

	/**
	 * setDescription
	 * @param string $description
	 * @return String
	 */
	public function setDescription($description) {
		$this->description = $description;
		return $this->description;
	}

	/**
	 * setEmailAddress
	 * @param string $emailAddress
	 * @return String
	 */
	public function setEmailAddress($emailAddress) {
		$this->emailAddress = $emailAddress;
		return $this->emailAddress;
	}

	/**
	 * setLanguage
	 * @param string $language
	 * @return String
	 */
	public function setLanguage($language) {
		$this->language = $language;
		return $this->language;
	}

	/**
	 * setOrganisation
	 * @param string $organisation
	 * @return String
	 */
	public function setOrganisation($organisation) {
		$this->organisation = $organisation;
		return $this->organisation;
	}

	/**
	 * search
	 * @return Array
	 * @throws InvalidShareTypeException
	 */
	public static function search() {
		return [];
	}

	/**
	 * getMembers
	 * @return array
	 */
	public function getMembers() {
		return [];
	}

	/**
	 * getUserGroupChild
	 * @return UserGroupClass
	 */
	public static function getUserGroupChild($type, $id, $displayName = '', $emailAddress = '') {
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
