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

	/**
	 * User constructor.
	 * @param $id
	 * @param $displayName
	 */
	public function __construct(
		$id,
		$displayName = ''
	) {
		parent::__construct($id, self::TYPE, $displayName);
		$this->icon = self::ICON;

		if (\OC::$server->getAppManager()->isEnabledForUser('contacts')) {
			$this->contact = [];

			$parts = explode(":", $this->id);
			$this->id = end($parts);

			// Search for UID and FN
			// Before this implementation contacts where stored with their FN property
			// From now on, the contact's UID is used as identifier
			// TODO: Remove FN as search range for loading a contact in a polls version later than 1.6
			$contacts = \OC::$server->getContactsManager()->search($this->id, ['UID', 'FN']);

			if (count($contacts) === 1) {
				$this->contact = $contacts[0];
				$this->id = $this->contact['UID'];
				$this->displayName = isset($this->contact['FN']) ? $this->contact['FN'] : $this->displayName;
				$this->emailAddress = isset($this->contact['EMAIL'][0]) ? $this->contact['EMAIL'][0] : $this->emailAddress;
			} elseif (count($contacts) > 1) {
				throw new MultipleContactsFound('Multiple contacts found for id ' . $this->id);
			}

			$this->organisation = isset($this->contact['ORG']) ? $this->contact['ORG'] : '';

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
}
