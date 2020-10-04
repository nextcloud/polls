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

use OCP\IL10N;
use OCA\Polls\Interfaces\IUserObj;

class Email implements \JsonSerializable, IUserObj {
	public const TYPE = 'email';

	/** @var IL10N */
	private $l10n;

	/** @var string */
	private $id;

	/** @var string */
	private $displayName = '';

	/** @var string */
	private $emailAddress = '';

	/**
	 * User constructor.
	 * @param $id
	 * @param $emailAddress
	 * @param $displayName
	 */
	public function __construct(
		$id,
		$emailAddress = '',
		$displayName = ''
	) {
		$this->id = $id;
		$this->emailAddress = $emailAddress;
		$this->displayName = $displayName;

		$this->l10n = \OC::$server->getL10N('polls');
	}

	/**
	 * Get id
	 * @NoAdminRequired
	 * @return String
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * getUser
	 * @NoAdminRequired
	 * @return String
	 */
	public function getUser() {
		return $this->id;
	}

	/**
	 * Get user type
	 * @NoAdminRequired
	 * @return String
	 */
	public function getType() {
		return self::TYPE;
	}

	/**
	 * @NoAdminRequired
	 * @return String
	 */
	public function getLanguage() {
		return '';
	}

	/**
	 * Get displayName
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDisplayName() {
		if ($this->displayName) {
			return $this->displayName;
		}
		return $this->id;
	}

	/**
	 * Get additional description, if available
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDescription() {
		return \OC::$server->getL10N('polls')->t('External Email');
	}

	/**
	 * Get email address
	 * @NoAdminRequired
	 * @return String
	 */
	public function getEmailAddress() {
		if ($this->emailAddress) {
			return $this->emailAddress;
		}
		return $this->id;
	}

	/**
	 * @NoAdminRequired
	 * @return String
	 */
	public function getOrganisation() {
		return '';
	}

	/**
	 * Get icon class
	 * @NoAdminRequired
	 * @return String
	 */
	public function getIcon() {
		return 'icon-mail';
	}

	// no search right now
	public static function search($query) {
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
