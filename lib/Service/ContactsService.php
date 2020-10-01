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

namespace OCA\Polls\Service;

use OCA\Polls\Model\User;

class ContactsService {
	public function __construct(
	) {
		$this->enabled = \OC::$server->getContactsManager()->isEnabled();
	}

	/**
	 * Get a list of contacts
	 * @NoAdminRequired
	 * @param string $query
	 * @return User[]
	 */
	public function getContacts($query = '', $queryRange = ['FN', 'EMAIL', 'ORG', 'CATEGORIES']) {
		if (!$this->enabled || $query === '') {
			return [];
		}

		$contacts = [];

		foreach (\OC::$server->getContactsManager()->search($query, $queryRange) as $contact) {
			if (!array_key_exists('isLocalSystemBook', $contact) && array_key_exists('EMAIL', $contact)) {
				$contacts[] = new User(User::TYPE_CONTACT, $contact['UID']);
			}
		}
		return $contacts;
	}

	/**
	 * Get a list of contacts
	 * @NoAdminRequired
	 * @param string $query
	 * @return User[]
	 */
	public function getContactsGroupMembers($query = '') {
		if (!$this->enabled || $query === '') {
			return [];
		}

		$contacts = [];
		foreach (\OC::$server->getContactsManager()->search($query, ['CATEGORIES']) as $contact) {
			if (!array_key_exists('isLocalSystemBook', $contact)
				&& array_key_exists('EMAIL', $contact)
				&& in_array($query, explode(',', $contact['CATEGORIES']))
			) {
				$emailAdresses = $contact['EMAIL'];

				if (!is_array($emailAdresses)) {
					$emailAdresses = [$emailAdresses];
				} else {
					// take the first eMail address for now
					$emailAdresses = [$emailAdresses[0]];
				}

				foreach ($emailAdresses as $emailAddress) {
					$contacts[] = new User(User::TYPE_CONTACT, $contact['UID']);
				}
			}
		}
		return $contacts;
	}

	/**
	 * Get a list of contact groups
	 * @NoAdminRequired
	 * @param string $query
	 * @return Array
	 */
	public function getContactsGroups($query = '') {
		if (!$this->enabled || $query === '') {
			return [];
		}

		$contactGroups = [];
		$foundContacts = [];
		foreach (\OC::$server->getContactsManager()->search($query, ['CATEGORIES']) as $contact) {
			foreach (explode(',', $contact['CATEGORIES']) as $contactGroup) {
				if (strpos($contactGroup, $query) === 0 && !in_array($contactGroup, $foundContacts)) {
					$foundContacts[] = $contactGroup;
					$contactGroups[] = new User(User::TYPE_CONTACTGROUP, $contactGroup);
				};
			}
		}
		return $contactGroups;
	}
}
