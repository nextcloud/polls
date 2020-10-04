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
use OCA\Polls\Exceptions\MultipleContactsFound;
use OCA\Polls\Exceptions\ContactsNotEnabled;
use OCA\Polls\Interfaces\IUserObj;

class Contact implements \JsonSerializable, IUserObj {
	public const TYPE = 'contact';

	/** @var IL10N */
	private $l10n;

	/** @var string */
	private $id;

	/** @var string */
	private $type;

	/** @var string */
	private $displayName = '';

	/** @var string */
	private $desc = '';

	/** @var array */
	private $contact;

	/**
	 * User constructor.
	 * @param $type
	 * @param $id
	 * @param $displayName
	 */
	public function __construct(
		$id,
		$displayName = ''
	) {
		$this->id = $id;
		$this->displayName = $displayName;

		$this->l10n = \OC::$server->getL10N('polls');
		$this->load();
	}

	/**
	 * Get id
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
	 * Get user type
	 * @NoAdminRequired
	 * @return String
	 */
	public function getType() {
		return self::TYPE;
	}

	/**
	 * Get language of user, if type = TYPE_USER
	 * @NoAdminRequired
	 * @return String
	 */
	public function getLanguage() {
		return '';
	}

	/**
	 * Get displayName
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDisplayName() {
		return isset($this->contact['FN']) ? $this->contact['FN'] : $this->displayName;
	}

	/**
	 * Get additional description, if available
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDesc() {
		$desc = $this->getCategories();
		if (isset($this->contact['ORG'])) {
			array_unshift($desc, $this->getOrganisation());
		}
		if (count($desc) > 0) {
			return implode(", ", $desc);
		} else {
			return \OC::$server->getL10N('polls')->t('Contact');
		}
	}

	/**
	 * Get email address
	 * @NoAdminRequired
	 * @return String
	 */
	public function getEmailAddress() {
		return isset($this->contact['EMAIL'][0]) ? $this->contact['EMAIL'][0] : '';
	}

	/**
	 * Get organisation, if type = TYPE_CONTACT
	 * @NoAdminRequired
	 * @return String
	 */
	public function getOrganisation() {
		return isset($this->contact['ORG']) ? $this->contact['ORG'] : '';
	}

	/**
	 * getCategories
	 * @NoAdminRequired
	 * @return Array
	 */
	public function getCategories() {
		if (isset($this->contact['CATEGORIES'])) {
			return explode(',', $this->contact['CATEGORIES']);
		} else {
			return [];
		}
	}

	/**
	 * Get icon class
	 * @NoAdminRequired
	 * @return String
	 */
	public function getIcon() {
		return 'icon-mail';
	}

	/**
	 * Load contact, if type = TYPE_CONTACT
	 * @NoAdminRequired
	 * @return String
	 * @throws MultipleContactsFound
	 * @throws ContactsNotEnabled
	 */
	private function load() {
		$this->contact = [];
		$parts = explode(":", $this->id);
		$this->id = end($parts);
		if (\OC::$server->getAppManager()->isEnabledForUser('contacts')) {
			// Search for UID and FN
			// Before this implementation contacts where stored with their FN property
			// From now on, the contact's UID is used as identifier
			// TODO: Remove FN as search range for loading a contact in a polls version later than 1.6
			$contacts = \OC::$server->getContactsManager()->search($this->id, ['UID', 'FN']);

			if (count($contacts) === 1) {
				$this->contact = $contacts[0];
				$this->id = $this->contact['UID'];
			} elseif (count($contacts) > 1) {
				throw new MultipleContactsFound('Multiple contacts found for id '. $this->id);
			}
		} else {
			throw new ContactsNotEnabled();
		}
	}

	/**
	 * isEnabled
	 * @NoAdminRequired
	 * @return Boolean
	 */
	public static function isEnabled() {
		return \OC::$server->getAppManager()->isEnabledForUser('contacts');
	}

	/**
	 * listRaw
	 * @NoAdminRequired
	 * @param string $query
	 * @return Array
	 */
	public static function listRaw($query = '', $queryRange = ['FN', 'EMAIL', 'ORG', 'CATEGORIES']) {
		$contacts = [];
		if (\OC::$server->getAppManager()->isEnabledForUser('contacts')) {
			foreach (\OC::$server->getContactsManager()->search($query, $queryRange) as $contact) {
				if (!array_key_exists('isLocalSystemBook', $contact) && array_key_exists('EMAIL', $contact)) {
					$contacts[] = $contact;
				}
			}
		}
		return $contacts;
	}

	/**
	 * list
	 * @NoAdminRequired
	 * @param string $query
	 * @param array $queryRange
	 * @return self[]
	 */
	public static function search($query = '', $queryRange = ['FN', 'EMAIL', 'ORG', 'CATEGORIES']) {
		$contacts = [];
		foreach (self::listRaw($query, $queryRange) as $contact) {
			$contacts[] = new Self($contact['UID']);
		}
		return $contacts;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return	[
			'id'        	=> $this->getId(),
			'user'          => $this->id,
			'type'       	=> $this->getType(),
			'displayName'	=> $this->getDisplayName(),
			'organisation'	=> $this->getOrganisation(),
			'emailAddress'	=> $this->getEmailAddress(),
			'desc' 			=> $this->getDesc(),
			'icon'			=> $this->getIcon(),
			'categories'	=> $this->getCategories(),
			'isNoUser'		=> true,
			'isGuest'		=> true,
		];
	}
}
