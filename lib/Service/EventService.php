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

use OCP\IGroupManager;

use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\ShareMapper;

class EventService {
	private $mapper;
	private $shareMapper;
	private $groupMapper;

	public function __construct(
		EventMapper $mapper,
		ShareMapper $shareMapper,
		IGroupManager $groupManager
	) {
		$this->mapper = $mapper;
		$this->shareMapper = $shareMapper;
		$this->groupManager = $groupManager;
	}

	/**
	 * Check if current user is in the access list
	 * @param Array $accessList
	 * @return Boolean
	 */
	public function checkUserAccess($accessList) {
		foreach ($accessList as $accessItem) {
			if ($accessItem['type'] === 'user' && $accessItem['id'] === \OC::$server->getUserSession()->getUser()->getUID()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check If current user is member of a group in the access list
	 * @param Array $accessList
	 * @return Boolean
	 */
	public function checkGroupAccess($accessList) {
		foreach ($accessList as $accessItem) {
			if ($accessItem['type'] === 'group' && $this->groupManager->isInGroup(\OC::$server->getUserSession()->getUser()->getUID(), $accessItem['id'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Set the access right of the current user for the poll
	 * @param Array $event
	 * @param Array $shares
	 * @return String
	 */
	public function grantAccessAs($pollId, $currentUser = '') {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		}

		$event = $this->mapper->find($pollId);

		$grantAccessAs = 'none';

		if ($event->getOwner() === $currentUser) {
			$grantAccessAs = 'owner';
		} elseif ($event->getAccess() === 'public') {
			$grantAccessAs = 'public';
		} elseif ($event->getAccess() === 'registered' && \OC::$server->getUserSession()->isLoggedIn()) {
			$grantAccessAs = 'registered';
		} elseif ($event->getAccess() === 'hidden' && ($event->getowner() === \OC::$server->getUserSession()->getUser())) {
			$grantAccessAs = 'hidden';
		// } elseif ($this->checkUserAccess($shares)) {
		// 	$grantAccessAs = 'userInvitation';
		// } elseif ($this->checkGroupAccess($shares)) {
		// 	$grantAccessAs = 'groupInvitation';
		} elseif ($this->groupManager->isAdmin($currentUser)) {
			$grantAccessAs = 'admin';
		}

		return $grantAccessAs;
	}

}
