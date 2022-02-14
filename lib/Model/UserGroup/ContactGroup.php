<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Model\UserGroup;

use OCA\Polls\Helper\Container;
use OCA\Polls\Exceptions\ContactsNotEnabledExceptions;
use OCP\Contacts\IManager as IContactsManager;

class ContactGroup extends UserBase {
	public const TYPE = 'contactGroup';
	public const ICON = 'icon-group';

	public function __construct(
		string $id
	) {
		parent::__construct($id, self::TYPE);
		$this->icon = self::ICON;
		$this->description = Container::getL10N()->t('Contact group');
		$this->richObjectType = 'addressbook-contact';

		if (!self::isEnabled()) {
			throw new ContactsNotEnabledExceptions();
		}
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

	/**
	 * Get a list of contacts group members
	 * @return Contact[]
	 */
	public function getMembers(): array {
		$contacts = [];

		foreach (Container::queryClass(IContactsManager::class)->search($this->id, ['CATEGORIES']) as $contact) {
			if (array_key_exists('EMAIL', $contact)) {
				$contacts[] = new Contact($contact['UID']);
			}
		}

		return $contacts;
	}

	public static function isEnabled(): bool {
		return Container::isAppEnabled('contacts');
	}

	/**
	 * @return ContactGroup[]
	 */
	public static function search(string $query = ''): array {
		$contactGroups = [];
		$categories = [];
		if (self::isEnabled() && $query) {
			foreach (Container::queryClass(IContactsManager::class)->search($query, ['CATEGORIES']) as $contact) {
				// get all groups from the found contact and explode to array

				foreach (explode(',', $contact['CATEGORIES']) as $category) {
					if (stripos($category, $query) === 0) {
						$categories[] = $category;
					}
				}
			}
			foreach (array_unique($categories) as $contactGroup) {
				$contactGroups[] = new self($contactGroup);
			}
		}
		return $contactGroups;
	}
}
