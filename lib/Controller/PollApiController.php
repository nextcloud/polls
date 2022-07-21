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

use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Model\Acl;
use OCA\Polls\Service\PollService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class PollApiController extends BaseApiController {

	/** @var Acl */
	private $acl;

	/** @var PollService */
	private $pollService;

	public function __construct(
		string $appName,
		Acl $acl,
		IRequest $request,
		PollService $pollService
	) {
		parent::__construct($appName, $request);
		$this->acl = $acl;
		$this->pollService = $pollService;
	}

	/**
	 * Get list of polls
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function list(): JSONResponse {
		try {
			return new JSONResponse(['polls' => $this->pollService->list()], Http::STATUS_OK);
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
	 * Clone poll
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function transferPolls(string $sourceUser, string $targetUser): JSONResponse {
		try {
			return new JSONResponse(['transferred' => $this->pollService->transferPolls($sourceUser, $targetUser)], Http::STATUS_CREATED);
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
