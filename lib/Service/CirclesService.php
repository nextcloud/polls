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

namespace OCA\Polls\Service;

use OCA\Polls\Model\User;
use \OCA\Circles\Api\v1\Circles;

class CirclesService {
	public function __construct(
	) {
		$this->enabled = \OC::$server->getAppManager()->isEnabledForUser('circles');
	}

	/**
	 * Get a list of avaiable circles
	 * @NoAdminRequired
	 * @param string $query
	 * @param array $skip - circle names to skip in return array
	 * @return Array
	 */
	public function getCircles($query = '', $skip = []) {
		if (!$this->enabled || $query === '') {
			return [];
		}

		$circles = [];


		foreach (\OCA\Circles\Api\v1\Circles::listCircles(\OCA\Circles\Model\Circle::CIRCLES_ALL, $query) as $circle) {
			if (!in_array($circle->getId(), $skip)) {
				$displayName = $circle->getName();

				$circles[] = new User(User::TYPE_CIRCLE, $circle->getUniqueId());
			}
		}
		return $circles;
	}


	/**
	 * Get circle details
	 * @NoAdminRequired
	 * @param string $circleId
	 * @return String
	 */
	public function getDisplayName($circleId = 0) {
		if (!$this->enabled) {
			return 'NixCircle';
		}
		return \OCA\Circles\Api\v1\Circles::detailsCircle($circleId)->getName();
	}

	/**
	 * Get circle details
	 * @NoAdminRequired
	 * @param string $circleId
	 * @return Array
	 */
	public function getDetails($circleId = 0) {
		if (!$this->enabled) {
			return 'Unknown circle';
		}
		return \OCA\Circles\Api\v1\Circles::detailsCircle($circleId);
	}

	/**
	 * Get a list of contacts
	 * @NoAdminRequired
	 * @param string $query
	 * @return User[]
	 */
	public function getCircleMembers($circleId) {
		if (!$this->enabled) {
			return [];
		}
		$members = [];
		foreach (\OCA\Circles\Api\v1\Circles::detailsCircle($circleId)->getMembers() as $circleMember) {
			if ($circleMember->getType() === Circles::TYPE_USER) {
				$members[] = new User(User::TYPE_USER, $circleMember->getUserId());
			} elseif ($circleMember->getType() === Circles::TYPE_GROUP) {
				$members[] = new User(User::TYPE_GROUP, $circleMember->getUserId());
			} elseif ($circleMember->getType() === Circles::TYPE_MAIL) {
				$members[] = new User(User::TYPE_EMAIL, $circleMember->getUserId());
			} elseif ($circleMember->getType() === Circles::TYPE_CONTACT) {
				$members[] = new User(User::TYPE_CONTACT, $circleMember->getUserId());
			}
		}
		return $members;
	}
}
