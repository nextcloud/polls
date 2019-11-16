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
use \OCP\ILogger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCP\Security\ISecureRandom;

use OCA\Polls\Db\Event;

use OCA\Polls\Model\Acl;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;

class ShareController extends Controller {

    private $logger;
    private $acl;
	private $mapper;
	private $userId;

	private $eventMapper;

	/**
	 * ShareController constructor.
	 * @param string $appName
	 * @param string $userId
	 * @param IRequest $request
	 * @param EventMapper $eventMapper
	 * @param ShareMapper $mapper
	 * @param Acl $acl
	 */
	public function __construct(
		string $appName,
		$userId,
		IRequest $request,
		ILogger $logger,
		ShareMapper $mapper,
		EventMapper $eventMapper,
		Acl $acl
	) {
		parent::__construct($appName, $request);
        $this->logger = $logger;
		$this->userId = $userId;
		$this->mapper = $mapper;
		$this->eventMapper = $eventMapper;
		$this->acl = $acl;
	}

	/**
	 * get
	 * Read all shares of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {
		// $this->acl->setPollId($pollId)
		if ($this->acl->setPollId($pollId)->getAllowEdit()) {
			try {
				$event = $this->eventMapper->find($pollId);
				$shares = $this->mapper->findByPoll($pollId);
			} catch (DoesNotExistException $e) {
				return new DataResponse($e, Http::STATUS_NOT_FOUND);
			}

			return new DataResponse((array) $shares, Http::STATUS_OK);
		} else {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

	}

	/**
	 * write
	 * Write a new share to the db and returns the new share as array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param string $message
	 * @return DataResponse
	 */
	public function write($pollId, $share) {
		$this->acl->setPollId($pollId);
		if (!$this->acl->getAllowEdit()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		$newShare = new Share();
		$newShare->setType($share['type']);
		$newShare->setPollId($share['pollId']);
		$newShare->setUserId($share['userId']);
		$newShare->setUserEmail($share['userEmail']);
		$newShare->setToken(\OC::$server->getSecureRandom()->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		));

		try {
			$newShare = $this->mapper->insert($newShare);
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
		}

		return new DataResponse($newShare, Http::STATUS_OK);

	}

	/**
	 * getByToken
	 * Get pollId by token
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function getByToken($token) {
		try {
			$share = $this->mapper->findByToken($token);
		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		} finally {
			return new DataResponse($share, Http::STATUS_OK);
		}
	}

	public function remove($share) {
		try {
			if ($this->acl->setPollId($share['pollId'])->getAllowEdit()) {
				$this->mapper->remove($share['id']);

				return new DataResponse(array(
					'action' => 'deleted',
					'shareId' => $share['id']
				), Http::STATUS_OK);
			} else {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}
}
