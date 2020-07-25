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

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\VoteService;


class VoteController extends Controller {

	/** @var VoteService */
	private $voteService;

	/**
	 * VoteController constructor
	 * @param string $appName
	 * @param IRequest $request
	 * @param VoteService $voteService
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		VoteService $voteService
	) {
		parent::__construct($appName, $request);
		$this->voteService = $voteService;
	}

	/**
	 * Read all votes of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param int $pollId
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
	 * @param int $optionId
	 * @param string $setTo
	 * @return DataResponse
	 */
	public function set($optionId, $setTo) {
		try {
			return new DataResponse($this->voteService->set($optionId, $setTo), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Option or poll not found'], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Remove user from poll
	 * @NoAdminRequired
	 * @param string $userId
	 * @param int $pollId
	 * @return DataResponse
	 */
	public function delete($pollId, $userId) {
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
	 * Set vote with token
	 * @NoAdminRequired
	 * @PublicPage
	 * @param Array $option
	 * @param string $setTo
	 * @param string $token
	 * @return DataResponse
	 */
	public function setByToken($optionId, $setTo, $token) {
		try {
			return new DataResponse($this->voteService->set($optionId, $setTo, $token), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Option not found'], Http::STATUS_NOT_FOUND);
		}

	}

	/**
	 * Read all votes of a poll based on a share token and return list as array
	 * @NoAdminRequired
	 * @PublicPage
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
