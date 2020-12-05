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

	public function __construct(
		string $appName,
		IRequest $request,
		SystemService $systemService
	) {
		parent::__construct($appName, $request);
		$this->systemService = $systemService;
	}

	/**
	 * Get a combined list of NC users, groups and contacts
	 * @NoAdminRequired
	 * $query
	 * $getGroups - search in groups
	 * $getUsers - search in site users
	 * $getContacts - search in contacs
	 * $getContactGroups - search in contacs
	 * $skipGroups - group names to skip in return array
	 * $skipUsers - user names to skip in return array
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
	): DataResponse {
		return new DataResponse(['siteusers' => $this->systemService->getSiteUsersAndGroups(
			$query, $getGroups, $getUsers, $getContacts, $getContactGroups, $getMail, $skipGroups, $skipUsers)], Http::STATUS_OK);
	}
}
