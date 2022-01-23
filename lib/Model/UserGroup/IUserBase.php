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

use DateTimeZone;

interface IUserBase {
	public const TYPE = 'generic';
	public const TYPE_PUBLIC = 'public';
	public const TYPE_EXTERNAL = 'external';
	public const TYPE_CIRCLE = Circle::TYPE;
	public const TYPE_CONTACT = Contact::TYPE;
	public const TYPE_CONTACTGROUP = ContactGroup::TYPE;
	public const TYPE_EMAIL = Email::TYPE;
	public const TYPE_GROUP = Group::TYPE;
	public const TYPE_USER = User::TYPE;
	public const TYPE_ADMIN = Admin::TYPE;

	/**
	 * @return string
	 */
	public function getId(): string;

	/**
	 * @return string
	 */
	public function getPublicId(): string;

	/**
	 * @return string
	 */
	public function getUser(): string;

	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * @return string
	 */
	public function getLanguage(): string;

	/**
	 * @return DateTimeZone
	 */
	public function getTimeZone(): DateTimeZone;

	/**
	 * @return string
	 */
	public function getLocale(): string;

	/**
	 * @return string
	 */
	public function getDisplayName(): string;

	/**
	 * @return string
	 */
	public function getDescription(): string;

	/**
	 * @return string
	 */
	public function getIcon(): string;

	/**
	 * @return string
	 */
	public function getEmailAddress(): string;

	/**
	 * @return string
	 */
	public function getOrganisation(): string;

	/**
	 * @return string[]
	 */
	public function getCategories(): array;

	/**
	 * @return bool
	 */
	public function getIsNoUser(): bool;

	/**
	 * @return string
	 */
	public function setType(string $type): string;

	/**
	 * @return string
	 */
	public function setDisplayName(string $displayName): string;

	/**
	 * @return string
	 */
	public function setDescription(string $description): string;

	/**
	 * @return string
	 */
	public function setEmailAddress(string $emailAddress) : string;

	/**
	 * @return string
	 */
	public function setLanguage(string $language): string;

	/**
	 * @return string
	 */
	public function setLocale(string $locale): string;

	/**
	 * @return string
	 */
	public function setOrganisation(string $organisation): string;

	/**
	 * @return array
	 */
	public function getRichObjectString() : array;

	/**
	 * Default is array with self as single element
	 * @return array
	 */
	public function getMembers(): array ;
}
