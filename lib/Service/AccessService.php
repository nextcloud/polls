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

use OCA\Polls\Db\VoteMapper;

class AccessService {

	private $eventMapper;

	public function __construct(
		EventMapper $eventMapper,

	) {
		$this->eventMapper = $eventMapper;
	}


	/**
	 * Evaluates the access level
	 * @NoAdminRequired
	 * @param Array $pollId
	 * @param Array $userId
	 * @return String
	 */
	public function getAccessLevel($pollId, $userId = '') {
		if ($userId = '' && \OC::$server->getUserSession()->isLoggedIn()) {
			$userId = \OC::$server->getUserSession()->getUser()->getUID();
		}

		$event = $this->eventMapper->find($pollId);

		$accessLevel = 'none';

		if ($event->getOwner() === $userId) {
			$accessLevel = 'owner';
		} elseif ($event->getAccess() === 'public') {
			$accessLevel = 'public';
		} elseif ($event->getAccess() === 'registered' && \OC::$server->getUserSession()->isLoggedIn()) {
			$accessLevel = 'registered';
		} elseif ($event->getAccess() === 'hidden' && ($event->getOwner() === \OC::$server->getUserSession()->getUser())) {
			$accessLevel = 'hidden';
		} elseif ($this->groupManager->isAdmin($userId)) {
			$accessLevel = 'admin';
		}

		return $accessLevel;
	}

}
