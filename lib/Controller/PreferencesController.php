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

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;


use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCP\IGroupManager;

use OCA\Polls\Db\Preferences;
use OCA\Polls\Db\PreferencesMapper;

class PreferencesController extends Controller {

	private $userId;
	private $preferencesMapper;

	private $groupManager;
	private $pollMapper;
	private $anonymizer;
	private $acl;

	/**
	 * PreferencesController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param PreferencesMapper $preferencesMapper
	 */

	public function __construct(
		string $appName,
		$userId,
		IRequest $request,
		PreferencesMapper $preferencesMapper
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->preferencesMapper = $preferencesMapper;
	}


	/**
	 * get
	 * Read all preferences
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get() {

		try {
			return new DataResponse($this->preferencesMapper->find($this->userId), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

	}

	/**
	 * write
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @param string $userId
	 * @param string $message
	 * @return DataResponse
	 */
	public function write($settings) {
		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$preferences = $this->preferencesMapper->find($this->userId);
			$preferences->setPreferences(json_encode($settings));
			$preferences->setTimestamp(time());
			$preferences = $this->preferencesMapper->update($preferences);
		} catch (\Exception $e) {
			$preferences = new Preferences();
			$preferences->setUserId($this->userId);
			$preferences->setPreferences(json_encode($settings));
			$preferences->setTimestamp(time());
			$preferences = $this->preferencesMapper->insert($preferences);
		}

		return new DataResponse($preferences, Http::STATUS_OK);

	}

	// /**
	//  * delete
	//  * Delete Preferences
	//  * @NoAdminRequired
	//  * @param int $pollId
	//  * @param string $message
	//  * @return DataResponse
	//  */
	// public function delete($userId) {
	// 	if (!\OC::$server->getUserSession()->isLoggedIn()) {
	// 		return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
	// 	}
	//
	// 	try {
	// 		$this->preferencesMapper->delete($userId);
	// 	} catch (\Exception $e) {
	// 		return new DataResponse($e, Http::STATUS_CONFLICT);
	// 	}
	//
	// 	return new DataResponse(['deleted' => $userId], Http::STATUS_OK);
	//
	// }

}
