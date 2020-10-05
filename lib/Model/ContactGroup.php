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

class ContactGroup extends UserGroupClass {
	public const TYPE = 'contactGroup';
	public const ICON = 'icon-group';

	/**
	 * Group constructor.
	 * @param $id
	 * @param $displayName
	 */
	public function __construct(
		$id,
		$displayName = ''
	) {
		parent::__construct($id, self::TYPE, $id);
		$this->icon = self::ICON;
		$this->description = \OC::$server->getL10N('polls')->t('Contact group');
	}

	/**
	 * listRaw
	 * @NoAdminRequired
	 * @param string $query
	 * @return Array
	 */
	public static function listRaw($query = '') {
		$contactGroups = [];
		if (\OC::$server->getContactsManager()->isEnabled()) {
			// find contact, which are member of the requested Group
			foreach (\OC::$server->getContactsManager()->search($query, ['CATEGORIES']) as $contact) {
				// get all groups from the found contact and explode to array
				$temp = explode(',', $contact['CATEGORIES']);
				foreach ($temp as $contactGroup) {
					if (stripos($contactGroup, $query) === 0) {
						$contactGroups[] = $contactGroup;
					}
				}
			}
		}
		return array_unique($contactGroups);
	}

	/**
	 * Get a list of contact groups
	 * @NoAdminRequired
	 * @param string $query
	 * @return Array
	 */
	public static function search($query = '') {
		$contactGroups = [];
		if (\OC::$server->getContactsManager()->isEnabled() && $query) {
			foreach (self::listRaw($query) as $contactGroup) {
				$contactGroups[] = new self($contactGroup);
			}
		}
		return $contactGroups;
	}

	/**
	 * Get a list of contacts group members
	 * @NoAdminRequired
	 * @return Contact[]
	 */
	public function getMembers() {
		if (\OC::$server->getContactsManager()->isEnabled()) {
			$contacts = [];
			foreach (\OC::$server->getContactsManager()->search($this->id, ['CATEGORIES']) as $contact) {
				if (array_key_exists('EMAIL', $contact)) {
					$contacts[] = new Contact($contact['UID']);
				}
			}
			return $contacts;
		}
		return [];
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return	[
			'id'        	=> $this->id,
			'user'          => $this->id,
			'type'       	=> $this->getType(),
			'displayName'	=> $this->getDisplayName(),
			'organisation'	=> $this->getOrganisation(),
			'emailAddress'	=> $this->getEmailAddress(),
			'desc' 			=> $this->getDescription(),
			'icon'			=> $this->getIcon(),
			'isNoUser'		=> true,
			'isGuest'		=> true,
		];
	}
}
