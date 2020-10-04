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

use OCP\IL10N;
use OCA\Polls\Interfaces\IUserObj;

class ContactGroup implements \JsonSerializable, IUserObj {
	public const TYPE = 'contactGroup';

	/** @var IL10N */
	private $l10n;

	/** @var string */
	private $id;

	/** @var string */
	private $displayName = '';

	private $group;

	/**
	 * Group constructor.
	 * @param $id
	 * @param $displayName
	 */
	public function __construct(
		$id,
		$displayName = ''
	) {
		$this->id = $id;
		$this->displayName = $displayName;
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
	 * getType
	 * @NoAdminRequired
	 * @return String
	 */
	public function getType() {
		return self::TYPE;
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
	 * getlanguage
	 * @NoAdminRequired
	 * @return String
	 */
	public function getLanguage() {
		return '';
	}

	/**
	 * getDisplayName
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDisplayName() {
		return $this->id;
	}

	/**
	 * getOrganisation
	 * @NoAdminRequired
	 * @return String
	 */
	public function getOrganisation() {
		return '';
	}

	/**
	 * getEmailAddress
	 * @NoAdminRequired
	 * @return String
	 */
	public function getEmailAddress() {
		return '';
	}

	/**
	 * getDesc
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDesc() {
		return \OC::$server->getL10N('polls')->t('Contact group');
	}

	/**
	 * getIcon
	 * @NoAdminRequired
	 * @return String
	 */
	public function getIcon() {
		return 'icon-group';
	}

	/**
	 * listRaw
	 * @NoAdminRequired
	 * @param string $query
	 * @return Array
	 */
	public static function listRaw($query = '') {
		$contactGroups = [];
		if (\OC::$server->getContactsManager()->isEnabled()) {
			// find contact, which are member of the requested Group
			foreach (\OC::$server->getContactsManager()->search($query, ['CATEGORIES']) as $contact) {
				// get all groups from the found contact and explode to array
				$temp = explode(',', $contact['CATEGORIES']);
				foreach ($temp as $contactGroup) {
					if (stripos($contactGroup, $query) === 0) {
						$contactGroups[] = $contactGroup;
					}
				}
			}
		}
		return array_unique($contactGroups);
	}

	/**
	 * Get a list of contact groups
	 * @NoAdminRequired
	 * @param string $query
	 * @return Array
	 */
	public static function search($query = '') {
		if (\OC::$server->getContactsManager()->isEnabled() && $query) {
			$contactGroups = [];
			foreach (self::listRaw($query) as $contactGroup) {
				$contactGroups[] = new self($contactGroup);
			}
		}
		return $contactGroups;
	}

	/**
	 * Get a list of contacts group members
	 * @NoAdminRequired
	 * @param string $query
	 * @return Contact[]
	 */
	public function getMembers() {
		if (\OC::$server->getContactsManager()->isEnabled()) {
			$contacts = [];
			foreach (\OC::$server->getContactsManager()->search($this->id, ['CATEGORIES']) as $contact) {
				if (!array_key_exists('isLocalSystemBook', $contact)
					&& array_key_exists('EMAIL', $contact)
					&& in_array($query, explode(',', $contact['CATEGORIES']))
				) {
					$emailAdresses = $contact['EMAIL'];

					if (!is_array($emailAdresses)) {
						$emailAdress = $emailAdresses;
					} else {
						// take the first eMail address for now
						$emailAdress = $emailAdresses[0];
					}
					$contacts[] = new Contact($contact['UID']);
				}
			}
			return $contacts;
		}
		return [];
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return	[
			'id'        	=> $this->id,
			'user'          => $this->id,
			'type'       	=> $this->getType(),
			'displayName'	=> $this->getDisplayName(),
			'organisation'	=> $this->getOrganisation(),
			'emailAddress'	=> $this->getEmailAddress(),
			'desc' 			=> $this->getDesc(),
			'icon'			=> $this->getIcon(),
			'isNoUser'		=> true,
			'isGuest'		=> true,
		];
	}
}
