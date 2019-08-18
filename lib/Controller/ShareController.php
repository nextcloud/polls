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

use OCP\Security\ISecureRandom;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;

class ShareController extends Controller {

	private $mapper;
	private $userId;

	/**
	 * ShareController constructor.
	 * @param string $appName
	 * @param string $userId
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param ShareMapper $mapper
	 */
	public function __construct(
		$appName,
		$UserId,
		IRequest $request,
		ShareMapper $mapper
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
	}


	/**
	 * getByHash
	 * Get pollId by hash
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param String $hash
	 * @return DataResponse
	 */
	public function getByHash($hash) {
		try {
			$share = $this->mapper->findByHash($hash);
		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		} finally {
			return new DataResponse($share, Http::STATUS_OK);
		}
	}

	/**
	 * generateHash
	 * Generate a new Hash and save to shares db
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param string $type
	 * @param string $userId
	 * @return DataResponse
	 */
	public function generateHash($pollId, $type, $userId) {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		$share = new Share();
		$share->setPollId($pollId);
		$share->setUserId($userId);
		$share->setType($type);
		$share->setHash(\OC::$server->getSecureRandom()->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		));

		try {
			$id = $this->mapper->insert($share)->getId();
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
		} finally {
			return new DataResponse(array(
				'id' => $id,
				'pollId' => $pollId,
				'userId' => $userId,
				'type' => $type,
				'hash' => $hash
			), Http::STATUS_OK);
		}
	}
}
