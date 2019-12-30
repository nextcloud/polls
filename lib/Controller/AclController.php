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

use OCP\IRequest;

use OCA\Polls\Model\Acl;


class AclController extends Controller {

	private $acl;

	/**
	 * PageController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param Acl $acl
	 */
	public function __construct(
		$appName,
		IRequest $request,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->acl = $acl;
	}

	/**
	 * Read acl with poll id for current user
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return array
	 */
	public function get($id) {
		$acl = $this->acl->setPollId($id);
		// $acl = $this->acl->setUserId('dartcafe');
		return new DataResponse($acl, Http::STATUS_OK);
	}

	/**
	 * Read acl with share token
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return array
	 */
	public function getByToken($token) {
		$acl = $this->acl->setToken($token);
		return new DataResponse($acl, Http::STATUS_OK);

	}

}
