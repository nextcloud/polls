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

namespace OCA\Polls\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCA\Polls\Service\SystemService;

use OCP\IRequest;

class SystemController extends Controller {

	/** @var SystemService */
	private $systemService;

	/**
	 * SystemController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param SystemService $systemService
	 */

	public function __construct(
		string $appName,
		IRequest $request,
		SystemService $systemService
	) {
		parent::__construct($appName, $request);
		$this->systemService = $systemService;
	}

	/**
	 * Get a list of users
	 * @NoAdminRequired
	 * @param string $query
	 * @param array $skipUsers - usernames to skip in return array
	 * @return DataResponse
	 */
	public function getSiteUsers($query = '', $skipUsers = []) {
		return new DataResponse(['users' => $this->systemService->getSiteUsers($query, $skipUsers)], Http::STATUS_OK);
	}

	/**
	 * Get a list of user groups
	 * @NoAdminRequired
	 * @param string $query
	 * @param array $skipGroups - group names to skip in return array
	 * @return DataResponse
	 */
	public function getSiteGroups($query = '', $skipGroups = []) {
		return new DataResponse(['groups' => $this->systemService->getSiteGroups($query, $skipGroups)], Http::STATUS_OK);
	}

	/**
	 * Get a list of contacts
	 * @NoAdminRequired
	 * @param string $query
	 * @return DataResponse
	 */
	public function getContacts($query = '') {
		return new DataResponse(['contacts' => $this->systemService->getContacts($query)], Http::STATUS_OK);
	}

	/**
	 * Get a list of contact groups
	 * @NoAdminRequired
	 * @param string $query
	 * @return DataResponse
	 */
	public function getContactsGroups($query = '') {
		return new DataResponse(['contactGroups' => $this->systemService->getContactsGroups($query)], Http::STATUS_OK);
	}


	/**
	 * Get a combined list of NC users, groups and contacts
	 * @NoAdminRequired
	 * @param string $query
	 * @param bool $getGroups - search in groups
	 * @param bool $getUsers - search in site users
	 * @param bool $getContacts - search in contacs
	 * @param bool $getContactGroups - search in contacs
	 * @param array $skipGroups - group names to skip in return array
	 * @param array $skipUsers - user names to skip in return array
	 * @return DataResponse
	 */
	public function getSiteUsersAndGroups(
		$query = '',
		$getGroups = true,
		$getUsers = true,
		$getContacts = true,
		$getContactGroups = true,
		$getMail = false,
		$skipGroups = [],
		$skipUsers = []
	) {
		return new DataResponse(['siteusers' => $this->systemService->getSiteUsersAndGroups(
			$query, $getGroups, $getUsers, $getContacts, $getContactGroups, $getMail, $skipGroups, $skipUsers)], Http::STATUS_OK);
	}

	/**
	 * Validate it the user name is reservrd
	 * return false, if this username already exists as a user or as
	 * a participant of the poll
	 * @NoAdminRequired
	 * @PublicPage
	 * @return DataResponse
	 */
	public function validatePublicUsername($pollId, $userName, $token) {
		try {
			return new DataResponse(['result' => $this->systemService->validatePublicUsername($pollId, $userName, $token), 'name' => $userName], Http::STATUS_OK);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Validate email address (simple validation)
	 * @NoAdminRequired
	 * @PublicPage
	 * @return DataResponse
	 */
	public function validateEmailAddress($emailAddress) {
		try {
			return new DataResponse(['result' => $this->systemService->validateEmailAddress($emailAddress), 'emailAddress' => $emailAddress], Http::STATUS_OK);
		} catch (\Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}
}
