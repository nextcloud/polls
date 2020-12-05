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

 use OCP\AppFramework\Db\DoesNotExistException;
 use OCA\Polls\Exceptions\Exception;

 use OCP\IRequest;
 use OCP\AppFramework\ApiController;
 use OCP\AppFramework\Http;
 use OCP\AppFramework\Http\DataResponse;

 use OCA\Polls\Service\PollService;

 class PollApiController extends ApiController {


	 /** @var PollService */
 	private $pollService;

 	/**
 	 * PollApiController constructor
 	 * @param string $appName
 	 * @param IRequest $request
 	 * @param PollService $pollService
 	 */

 	public function __construct(
		string $appName,
		IRequest $request,
		PollService $pollService
	) {
 		parent::__construct($appName, $request);
 		$this->pollService = $pollService;
 	}


 	/**
 	 * Get list of polls
 	 * @NoAdminRequired
 	 * @CORS
 	 * @NoCSRFRequired
 	 * @return DataResponse
 	 */

 	public function list() {
 		try {
 			return new DataResponse(['polls' => $this->pollService->list()], Http::STATUS_OK);
 		} catch (DoesNotExistException $e) {
 			return new DataResponse([], Http::STATUS_NOT_FOUND);
 		} catch (Exception $e) {
 			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
 		}
 	}


 	/**
 	 * get poll configuration
 	 * @NoAdminRequired
 	 * @CORS
 	 * @NoCSRFRequired
 	 * @param int $pollId
 	 * @return DataResponse
 	 */
 	public function get($pollId) {
 		try {
 			return new DataResponse(['poll' => $this->pollService->get($pollId)], Http::STATUS_OK);
 		} catch (DoesNotExistException $e) {
 			return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
 		} catch (Exception $e) {
 			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
 		}
 	}

 	/**
 	 * Add poll
 	 * @NoAdminRequired
 	 * @NoCSRFRequired
 	 * @CORS
 	 * @param Array $poll
 	 * @return DataResponse
 	 */

 	public function add($type, $title) {
 		try {
 			return new DataResponse(['poll' => $this->pollService->add($type, $title)], Http::STATUS_CREATED);
 		} catch (Exception $e) {
 			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
 		}
 	}

 	/**
 	 * Update poll configuration
 	 * @NoAdminRequired
 	 * @CORS
 	 * @NoCSRFRequired
 	 * @param int $pollId
 	 * @param array $poll
 	 * @return DataResponse
 	 */

 	public function update($pollId, $poll) {
 		try {
 			return new DataResponse(['poll' => $this->pollService->update($pollId, $poll)], Http::STATUS_OK);
 		} catch (DoesNotExistException $e) {
 			return new DataResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
 		} catch (Exception $e) {
 			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
 		}
 	}

 	/**
 	 * Switch deleted status (move to deleted polls)
 	 * @NoAdminRequired
 	 * @CORS
 	 * @NoCSRFRequired
 	 * @param int $pollId
 	 * @return DataResponse
 	 */

 	public function switchDeleted($pollId) {
 		try {
 			return new DataResponse(['poll' => $this->pollService->switchDeleted($pollId)], Http::STATUS_OK);
 		} catch (DoesNotExistException $e) {
 			return new DataResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
 		} catch (Exception $e) {
 			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
 		}
 	}

 	/**
 	 * Delete poll
 	 * @NoAdminRequired
 	 * @CORS
 	 * @NoCSRFRequired
 	 * @param int $pollId
 	 * @return DataResponse
 	 */

 	public function delete($pollId) {
 		try {
 			return new DataResponse(['poll' => $this->pollService->delete($pollId)], Http::STATUS_OK);
 		} catch (DoesNotExistException $e) {
 			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_OK);
 		} catch (Exception $e) {
 			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
 		}
 	}

 	/**
 	 * Clone poll
 	 * @NoAdminRequired
 	 * @CORS
 	 * @NoCSRFRequired
 	 * @param int $pollId
 	 * @return DataResponse
 	 */
 	public function clone($pollId) {
 		try {
 			return new DataResponse(['poll' => $this->pollService->clone($pollId)], Http::STATUS_CREATED);
 		} catch (DoesNotExistException $e) {
 			return new DataResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
 		} catch (Exception $e) {
 			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
 		}
 	}

 	/**
 	 * Collect email addresses from particitipants
 	 * @NoAdminRequired
 	 * @CORS
 	 * @NoCSRFRequired
 	 * @param Array $poll
 	 * @return DataResponse
 	 */

 	public function getParticipantsEmailAddresses($pollId) {
 		try {
 			return new DataResponse($this->pollService->getParticipantsEmailAddresses($pollId), Http::STATUS_OK);
 		} catch (DoesNotExistException $e) {
 			return new DataResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
 		} catch (Exception $e) {
 			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
 		}
 	}

 	/**
 	 * Get valid values for configuration options
 	 * @NoAdminRequired
 	 * @CORS
 	 * @NoCSRFRequired
 	 * @param Array $poll
 	 * @return DataResponse
 	 */

 	public function enum() {
 		return new DataResponse($this->pollService->getValidEnum(), Http::STATUS_OK);
 	}
 }
