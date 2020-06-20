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
 use OCA\Polls\Exceptions\EmptyTitleException;
 use OCA\Polls\Exceptions\InvalidAccessException;
 use OCA\Polls\Exceptions\InvalidShowResultsException;
 use OCA\Polls\Exceptions\InvalidPollTypeException;
 use OCA\Polls\Exceptions\NotAuthorizedException;

 use OCP\IRequest;
 use OCP\ILogger;
 use OCP\AppFramework\ApiController;
 use OCP\AppFramework\Http;
 use OCP\AppFramework\Http\DataResponse;

 use OCA\Polls\Service\PollService;

 class PollApiController extends ApiController {

 	private $logger;
 	private $pollService;

 	/**
 	 * PollController constructor.
 	 * @param string $appName
 	 * @param $userId
 	 * @param IRequest $request
 	 * @param ILogger $logger
 	 * @param PollService $pollService
 	 */

 	public function __construct(
 		string $appName,
 		IRequest $request,
 		ILogger $logger,
		PollService $pollService
 	) {
 		parent::__construct($appName, $request);
 		$this->logger = $logger;
 		$this->pollService = $pollService;
 	}


	/**
	 * list
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @CORS
	 * @return DataResponse
	 */

	public function list() {
		try {
			return new DataResponse($this->pollService->list(), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}


	/**
	 * get
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return array
	 */
 	public function get($pollId) {
		try {
			return new DataResponse($this->pollService->get($pollId), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse('Not found', Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
 	}

	/**
	 * write
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Array $poll
	 * @return DataResponse
	 */

	public function add($type, $title) {
		try {
			return new DataResponse($this->pollService->add($type, $title), Http::STATUS_CREATED);
		} catch (NotAuthorizedException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		} catch (InvalidPollTypeException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		} catch (EmptyTitleException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}

	/**
	 * write
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Array $poll
	 * @return DataResponse
	 */

	public function update($pollId, $poll) {
		try {
			return new DataResponse($this->pollService->update($pollId, $poll), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse('Poll not found', Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		} catch (InvalidAccessException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		} catch (InvalidShowResultsException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		} catch (EmptyTitleException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}

	/**
	 * delete
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Array $poll
	 * @return DataResponse
	 */

	public function delete($pollId) {
		try {
			return new DataResponse($this->pollService->delete($pollId), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse('Poll not found', Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}

	/**
	 * deletePermanently
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Array $poll
	 * @return DataResponse
	 */

	public function deletePermanently($pollId) {
		try {
			return new DataResponse($this->pollService->deletePermanently($pollId), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse('Poll not found', Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}

	}

	/**
	 * clone
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function clone($pollId) {
		try {
			return new DataResponse($this->pollService->clone($pollId), Http::STATUS_CREATED);
		} catch (DoesNotExistException $e) {
			return new DataResponse('Poll not found', Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse($e->getMessage(), $e->getStatus());
		}
	}

	/**
	 * enum
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Array $poll
	 * @return DataResponse
	 */

	public function enum() {
		return new DataResponse($this->pollService->getValidEnum(), Http::STATUS_OK);
	}


}
