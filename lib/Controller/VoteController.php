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

// use Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCP\ILogger;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\VoteService;


class VoteController extends Controller {

	private $voteService;
	private $logger;

	/**
	 * VoteController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param VoteService $voteService

	 */
	public function __construct(
		string $appName,
		ILogger $logger,
		IRequest $request,
		VoteService $voteService
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->voteService = $voteService;
	}

	/**
	 * Get all votes of given poll
	 * Read all votes of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {
		try {
			return new DataResponse($this->voteService->list($pollId), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'No votes'], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * set
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @param Array $option
	 * @param string $userId
	 * @param string $setTo
	 * @return DataResponse
	 */
	public function set($pollId, $option, $setTo) {
		try {
			return new DataResponse($this->voteService->set($pollId, $option['pollOptionText'], $setTo), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Option not found'], Http::STATUS_NOT_FOUND);
		}
	}


	/**
	 * delete
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $voteId
	 * @param string $userId
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function delete($userId, $pollId) {
		try {
			return new DataResponse($this->voteService->delete($pollId, $userId), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => ''], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Public functions
	 */

	/**
	 * setByToken
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param Array $option
	 * @param string $setTo
	 * @param string $token
	 * @return DataResponse
	 */
	public function setByToken($option, $setTo, $token) {
		try {
			return new DataResponse($this->voteService->set(0, $option['pollOptionText'], $setTo, $token), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Option not found'], Http::STATUS_NOT_FOUND);
		}

	}

	/**
	 * getByToken
	 * Read all votes of a poll based on a share token and return list as array
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function getByToken($token) {
		try {
			return new DataResponse($this->voteService->list(null, $token), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'No votes'], Http::STATUS_NOT_FOUND);
		}

	}

}
