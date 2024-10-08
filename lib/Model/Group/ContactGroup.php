<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\Group;

use OCA\Polls\Exceptions\ContactsNotEnabledExceptions;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\User\Contact;
use OCA\Polls\Model\UserBase;
use OCP\Contacts\IManager as IContactsManager;

class ContactGroup extends UserBase {
	public const TYPE = 'contactGroup';

	public function __construct(
		string $id,
	) {
		parent::__construct($id, self::TYPE);
		$this->description = $this->l10n->t('Contact group');
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
