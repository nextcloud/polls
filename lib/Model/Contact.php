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

use OCP\App\IAppManager;
use OCP\Contacts\IManager as IContactsManager;
use OCA\Polls\Exceptions\MultipleContactsFound;
use OCA\Polls\Exceptions\ContactsNotEnabledExceptions;

class Contact extends UserGroupClass {
	public const TYPE = 'contact';
	public const ICON = 'icon-mail';

	/** @var Array */
	private $contact = [];

	public function __construct(
		string $id
	) {
		parent::__construct($id, self::TYPE);
		if (self::isEnabled()) {
			$this->icon = self::ICON;
			$this->getContact();
		} else {
			throw new ContactsNotEnabledExceptions();
		}
	}


	/**
	 * must use displayName for contact's user id, because contact id
	 * is not accessable outside the owners's scope
	 *
	 * @return string
	 */
	public function getPublicId(): string {
		return $this->displayName;
	}

	/**
	 * We just need the contact's UID, so make sure, the any prefix is removed
	 */
	private function resolveContactId(): void {
		$parts = explode(":", $this->id);
		$this->id = end($parts);
	}

	/**
	 * The contacts app just provides a search, so we have to load the contact
	 * after searching via the contact's id and use the first contact.
	 * Currently only the contact's first email address is supported
	 * From Version 1.5 on:
	 * For compatibility reasons, we have to search for the contacts name too.
	 * Before this implementation contacts where stored with their FN property.
	 * TODO: Remove FN as search range for loading a contact in a polls version
	 * later than 1.6.
	 */
	private function loadContact(): void {
		$contacts = self::listRaw($this->id, ['UID', 'FN']);

		// workaround fur multiple found UIDs
		// Don't throw an error, log the error and take the first entry
		if (count($contacts) > 1) {
			// throw new MultipleContactsFound('Multiple contacts found for id ' . $this->id);
			\OC::$server->getLogger()->error('Multiple contacts found for id ' . $this->id);
		}

		$this->contact = $contacts[0];
	}

	private function getContact(): void {
		$this->resolveContactId();
		$this->loadContact();

		$this->id = $this->contact['UID'];
		$this->displayName = $this->contact['FN'] ?? $this->displayName;
		$this->emailAddress = $this->contact['EMAIL'][0] ?? $this->emailAddress;
		$this->organisation = $this->contact['ORG'] ?? '';
		$this->categories = isset($this->contact['CATEGORIES']) ? explode(',', $this->contact['CATEGORIES']) : [];
		$description = $this->categories;

		if (isset($this->contact['ORG'])) {
			array_unshift($description, $this->organisation);
		}
		$this->description = count($description) ? implode(", ", $description) : \OC::$server->getL10N('polls')->t('Contact');
	}

	public static function isEnabled(): bool {
		return self::getContainer()->query(IAppManager::class)->isEnabledForUser('contacts');
	}

	/**
	 * List all contacts with email adresses
	 * excluding contacts from localSystemBook
	 *
	 * @param string[] $queryRange
	 */
	private static function listRaw(string $query = '', array $queryRange = ['FN', 'EMAIL', 'ORG', 'CATEGORIES']): array {
		$contacts = [];

		if (self::isEnabled()) {
			foreach (self::getContainer()->query(IContactsManager::class)->search($query, $queryRange) as $contact) {
				if (!array_key_exists('isLocalSystemBook', $contact) && array_key_exists('EMAIL', $contact)) {
					$contacts[] = $contact;
				}
			}
		}
		return $contacts;
	}

	/**
	 * @return Contact[]
	 */
	public static function search(string $query = '', array $queryRange = ['FN', 'EMAIL', 'ORG', 'CATEGORIES']): array {
		$contacts = [];
		foreach (self::listRaw($query, $queryRange) as $contact) {
			$contacts[] = new self($contact['UID']);
		}
		return $contacts;
	}
}
