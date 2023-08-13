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

use OCA\Polls\AppConstants;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Model\Acl;
use OCA\Polls\Service\PollService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class PollApiController extends BaseApiController {
	public function __construct(
		string $appName,
		IRequest $request,
		private Acl $acl,
		private PollService $pollService
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get list of polls
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function list(): JSONResponse {
		try {
			return new JSONResponse([AppConstants::APP_ID => $this->pollService->list()], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * get poll configuration
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function get(int $pollId): JSONResponse {
		try {
			return new JSONResponse(['poll' => $this->pollService->get($pollId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Add poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @CORS
	 */
	public function add(string $type, string $title): JSONResponse {
		try {
			return new JSONResponse(['poll' => $this->pollService->add($type, $title)], Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Update poll configuration
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function update(int $pollId, array $poll): JSONResponse {
		try {
			$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);
			
			return new JSONResponse([
				'poll' => $this->pollService->update($pollId, $poll),
				'acl' => $this->acl,
			], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Switch deleted status (move to deleted polls)
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function toggleArchive(int $pollId): JSONResponse {
		try {
			return new JSONResponse(['poll' => $this->pollService->toggleArchive($pollId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Delete poll
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function delete(int $pollId): JSONResponse {
		try {
			return new JSONResponse(['poll' => $this->pollService->delete($pollId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Clone poll
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function clone(int $pollId): JSONResponse {
		try {
			return new JSONResponse(['poll' => $this->pollService->clone($pollId)], Http::STATUS_CREATED);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Transfer all polls from one user to another (change owner of poll)
	 * @CORS
	 * @NoCSRFRequired
	 * @param string $sourceUser User to transfer polls from
	 * @param string $destinationUser User to transfer polls to
	 */
	public function transferPolls(string $sourceUser, string $destinationUser): JSONResponse {
		try {
			return new JSONResponse(['transferred' => $this->pollService->transferPolls($sourceUser, $destinationUser)], Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Transfer singe poll to another user (change owner of poll)
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $pollId Poll to transfer
	 * @param string $destinationUser User to transfer the poll to
	 */
	public function transferPoll(int $pollId, string $destinationUser): JSONResponse {
		try {
			return new JSONResponse(['transferred' => $this->pollService->transferPoll($pollId, $destinationUser)], Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Collect email addresses from particitipants
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function getParticipantsEmailAddresses(int $pollId): JSONResponse {
		try {
			return new JSONResponse($this->pollService->getParticipantsEmailAddresses($pollId), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Get valid values for configuration options
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function enum(): JSONResponse {
		return new JSONResponse($this->pollService->getValidEnum(), Http::STATUS_OK);
	}
}
