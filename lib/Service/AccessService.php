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

use OCP\ILogger;
use OCP\IGroupManager;
use OCA\Polls\Db\EventMapper;

class AccessService {

	private $groupManager;
	private $eventMapper;
	private $logger;

	/**
	 * PageController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param IRequest $request
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 */
	public function __construct(
		EventMapper $eventMapper,
		ILogger $logger,
		IGroupManager $groupManager

	) {
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->eventMapper = $eventMapper;
	}


	/**
	 * Evaluates the access level
	 * @NoAdminRequired
	 * @param Array $pollId
	 * @param Array $userId
	 * @return String
	 */
	private function getAccessLevel($pollId, $userId = '') {
		if ($userId === '' && \OC::$server->getUserSession()->isLoggedIn()) {
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

	/**
	 * Check, if user has edit right in this event
	 * @NoAdminRequired
	 * @param Array $pollId
	 * @return String
	 */
	public function userHasEditRights($pollId) {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			$event = $this->eventMapper->find($pollId);
			return ($event->getOwner() === \OC::$server->getUserSession()->getUser()->getUID()
				 || $this->groupManager->isAdmin(\OC::$server->getUserSession()->getUser()->getUID()));
		} else {
			return false;
		}
	}

	/**
	 * Check, if user is the poll owner
	 * @NoAdminRequired
	 * @param Array $pollId
	 * @return String
	 */
	public function userIsOwner($pollId) {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			$event = $this->eventMapper->find($pollId);
			return ($event->getOwner() === \OC::$server->getUserSession()->getUser()->getUID());
		} else {
			return false;
		}
	}

}
