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

use OCA\Circles\Api\v1\Circles;

use OCA\Polls\Exceptions\CirclesNotEnabled;
use OCA\Polls\Interfaces\IUserObj;

class Circle implements \JsonSerializable, IUserObj {
	public const TYPE = 'circle';

	/** @var string */
	private $id;

	private $circle;

	/**
	 * Group constructor.
	 * @param $id
	 * @param $displayName
	 */
	public function __construct(
		$id
	) {
		$this->id = $id;
		$this->load();
	}

	/**
	 * getId
	 * @NoAdminRequired
	 * @return String
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * getUser
	 * Necessary for the avatar component
	 * @NoAdminRequired
	 * @return String
	 */
	public function getUser() {
		return $this->id;
	}

	/**
	 * getType
	 * @NoAdminRequired
	 * @return String
	 */
	public function getType() {
		return self::TYPE;
	}

	/**
	 * getlanguage
	 * @NoAdminRequired
	 * @return String
	 */
	public function getLanguage() {
		return '';
	}

	/**
	 * getDisplayName
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDisplayName() {
		return Circles::detailsCircle($this->id)->getName();
	}

	/**
	 * getOrganisation
	 * @NoAdminRequired
	 * @return String
	 */
	public function getOrganisation() {
		return '';
	}

	/**
	 * getEmailAddress
	 * @NoAdminRequired
	 * @return String
	 */
	public function getEmailAddress() {
		return '';
	}

	/**
	 * getDescription
	 * @NoAdminRequired
	 * @return String
	 */
	public function getDescription() {
		return Circles::detailsCircle($this->id)->gettypeLongString();
	}

	/**
	 * getIcon
	 * @NoAdminRequired
	 * @return String
	 */
	public function getIcon() {
		return 'icon-circles';
	}

	/**
	 * load
	 * @NoAdminRequired
	 * @return Array
	 * @throws CirclesNotEnabled
	 */
	private function load() {
		if (\OC::$server->getAppManager()->isEnabledForUser('circles')) {
			$this->circle = Circles::detailsCircle($this->id);
		} else {
			throw new CirclesNotEnabled();
		}
	}

	/**
	 * isEnabled
	 * @NoAdminRequired
	 * @return Boolean
	 */
	public static function isEnabled() {
		return \OC::$server->getAppManager()->isEnabledForUser('circles');
	}

	/**
	 * listRaw
	 * @NoAdminRequired
	 * @param string $query
	 * @return Array
	 */
	public static function listRaw($query = '') {
		$circles = [];
		if (\OC::$server->getAppManager()->isEnabledForUser('circles')) {
			$circles = Circles::listCircles(\OCA\Circles\Model\Circle::CIRCLES_ALL, $query);
		}

		return $circles;
	}

	/**
	 * search
	 * @NoAdminRequired
	 * @param string $query
	 * @param array $skip - group names to skip in return array
	 * @return Circle[]
	 */
	public static function search($query = '', $skip = []) {
		$circles = [];
		foreach (self::listRaw($query) as $circle) {
			if (!in_array($circle->getUniqueId(), $skip)) {
				$circles[] = new Self($circle->getUniqueId());
			}
		}

		return $circles;
	}

	/**
	 * Get a list of circle members
	 * @NoAdminRequired
	 * @param string $query
	 * @return User[]
	 */
	public function getMembers() {
		$members = [];
		if (\OC::$server->getAppManager()->isEnabledForUser('circles')) {
			foreach (Circles::detailsCircle($this->id)->getMembers() as $circleMember) {
				if ($circleMember->getType() === Circles::TYPE_USER) {
					$members[] = new User($circleMember->getUserId());
				} elseif ($circleMember->getType() === Circles::TYPE_MAIL) {
					$members[] = new Email($circleMember->getUserId());
				} elseif ($circleMember->getType() === Circles::TYPE_CONTACT) {
					$members[] = new Contact($circleMember->getUserId());
				} else {
					continue;
				}
			}
		}
		return $members;
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
		];
	}
}
