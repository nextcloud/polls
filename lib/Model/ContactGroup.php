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

class ContactGroup extends UserGroupClass {
	public const TYPE = 'contactGroup';
	public const ICON = 'icon-group';

	public function __construct(
		$id
	) {
		parent::__construct($id, self::TYPE);
		$this->icon = self::ICON;
		$this->description = \OC::$server->getL10N('polls')->t('Contact group');
	}

	/**
	 * @return string
	 */
	public function getDisplayName(): string {
		if (!$this->displayName) {
			return $this->id;
		}
		return $this->displayName;
	}

	public static function isEnabled(): bool {
		return self::getContainer()->query(IAppManager::class)->isEnabledForUser('contacts');
	}

	/**
	 * @return ContactGroup[]
	 */
	public static function search(string $query = ''): array {
		$contactGroups = [];
		if (self::isEnabled() && $query) {
			// foreach (\OC::$server->getContactsManager()->search($query, ['CATEGORIES']) as $contact) {
			foreach (self::getContainer()->query(IContactsManager::class)->search($query, ['CATEGORIES']) as $contact) {
				// get all groups from the found contact and explode to array
				$temp = explode(',', $contact['CATEGORIES']);
				foreach ($temp as $contactGroup) {
					if (stripos($contactGroup, $query) === 0) {
						$contactGroups[] = $contactGroup;
					}
				}
			}

			foreach (array_unique($contactGroups) as $contactGroup) {
				$contactGroups[] = new self($contactGroup);
			}
		}
		return $contactGroups;
	}

	/**
	 * Get a list of contacts group members
	 * @return Contact[]
	 */
	public function getMembers() {
		$contacts = [];
		if (self::isEnabled()->isEnabled()) {
			foreach (self::getContainer()->query(IContactsManager::class)->search($this->id, ['CATEGORIES']) as $contact) {
				if (array_key_exists('EMAIL', $contact)) {
					$contacts[] = new Contact($contact['UID']);
				}
			}
		}
		return $contacts;
	}
}
