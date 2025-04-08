<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\User;

use OCA\Polls\Exceptions\ContactsNotEnabledExceptions;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\UserBase;
use OCP\Contacts\IManager as IContactsManager;
use Psr\Log\LoggerInterface;

class Contact extends UserBase {
	/** @var string */
	public const TYPE = 'contact';

	protected LoggerInterface $logger;
	private array $contact = [];

	public function __construct(string $id) {
		parent::__construct($id, self::TYPE);
		$this->description = $this->l10n->t('Contact');
		$this->richObjectType = 'addressbook-contact';
		$this->logger = Container::queryClass(LoggerInterface::class);

		if (self::isEnabled()) {
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
	public function getSafeId(): string {
		return $this->displayName;
	}

	/**
	 * In shares, contact users are used as email users
	 * return Email::TYPE (Share),
	 *
	 * @return string
	 **/
	public function getShareType(): string {
		return UserBase::TYPE_EMAIL;
	}

	/**
	 * In shares, contact users are used as email users
	 * return Email::TYPE (Share),
	 *
	 * @return string
	 **/
	public function getShareUserId(): string {
		if ($this->emailAddress !== '') {
			return $this->emailAddress;
		}
		return $this->id;
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
			$this->logger->warning('Multiple contacts found for {id} ', ['id' => $this->id]);
		}

		$this->contact = $contacts[0];
	}

	private function getContact(): void {
		$this->loadContact();

		$this->id = $this->contact['UID'];
		$this->displayName = $this->contact['FN'] ?? $this->displayName;
		$this->emailAddress = $this->contact['EMAIL'][0] ?? $this->emailAddress;
		$this->organisation = $this->contact['ORG'] ?? '';
		$this->categories = isset($this->contact['CATEGORIES']) ? explode(',', $this->contact['CATEGORIES']) : [];
	}

	public function getDescription(): string {
		$description = $this->getCategories();

		if (isset($this->contact['ORG'])) {
			array_unshift($description, $this->getOrganisation());
		}

		if ($this->getEmailAddress()) {
			array_unshift($description, $this->getEmailAddress());
		}

		return count($description) ? implode(', ', $description) : $this->l10n->t('Contact');
	}



	public static function isEnabled(): bool {
		return Container::isAppEnabled('contacts');
	}

	/**
	 * List all contacts with email addresses
	 * excluding contacts from localSystemBook
	 *
	 * @param string[] $queryRange
	 */
	private static function listRaw(string $query = '', array $queryRange = ['FN', 'EMAIL', 'ORG', 'CATEGORIES']): array {
		$contacts = [];

		if (self::isEnabled()) {
			foreach (Container::queryClass(IContactsManager::class)->search($query, $queryRange) as $contact) {
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
