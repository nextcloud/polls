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

use OCA\Polls\Exceptions\MultipleContactsFound;
use OCA\Polls\Exceptions\ContactsNotEnabled;

class Contact extends UserGroupClass {
	public const TYPE = 'contact';
	public const ICON = 'icon-mail';

	/** @var Array */
	private $contact = [];

	/**
	 * Contact constructor.
	 * @param $id
	 * @param $displayName
	 */
	public function __construct(
		$id
	) {
		parent::__construct($id, self::TYPE);
		$this->icon = self::ICON;
		$this->getContact();
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
	 * resolveContact
	 * We just need the contact's UID, so make sure, the any prefix is removed
	 * @NoAdminRequired
	 */
	private function resolveContactId() {
		$parts = explode(":", $this->id);
		$this->id = end($parts);
	}

	/**
	 * loadContact
	 * @NoAdminRequired
	 * The contacts app just provides a search, so we have to load the contact
	 * after searching via the contact's id and use the first contact.
	 *
	 * Currently only the contact's first email address is supported
	 *
	 * From Version 1.5 on:
	 * For compatibility reasons, we have to search for the contacts name too.
	 * Before this implementation contacts where stored with their FN property.
	 *
	 * TODO: Remove FN as search range for loading a contact in a polls version
	 * later than 1.6.
	 */
	private function loadContact() {
		$contacts = self::listRaw($this->id, ['UID', 'FN']);

		if (count($contacts) > 1) {
			throw new MultipleContactsFound('Multiple contacts found for id ' . $this->id);
		}

		$this->contact = $contacts[0];
	}

	private function getContact() {
		if (\OC::$server->getAppManager()->isEnabledForUser('contacts')) {
			$this->resolveContactId();
			$this->loadContact();

			$this->id = $this->contact['UID'];
			$this->displayName = isset($this->contact['FN']) ? $this->contact['FN'] : $this->displayName;
			$this->emailAddress = isset($this->contact['EMAIL'][0]) ? $this->contact['EMAIL'][0] : $this->emailAddress;
			$this->organisation = isset($this->contact['ORG']) ? $this->contact['ORG'] : '';
			$this->categories = isset($this->contact['CATEGORIES']) ? explode(',', $this->contact['CATEGORIES']) : [];


			if (isset($this->contact['CATEGORIES'])) {
				$this->categories = explode(',', $this->contact['CATEGORIES']);
			} else {
				$this->categories = [];
			}

			$description = $this->categories;

			if (isset($this->contact['ORG'])) {
				array_unshift($description, $this->organisation);
			}

			if (count($description) > 0) {
				$this->description = implode(", ", $description);
			} else {
				$this->description = \OC::$server->getL10N('polls')->t('Contact');
			}
		} else {
			throw new ContactsNotEnabled();
		}
	}

	/**
	 * listRaw
	 * @NoAdminRequired
	 * List all contacts with email adresses
	 * excluding contacts from localSystemBook
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
}
