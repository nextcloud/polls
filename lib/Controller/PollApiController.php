<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\AppConstants;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Model\Acl as Acl;
use OCA\Polls\Service\PollService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class PollApiController extends BaseApiController {
	public function __construct(
		string $appName,
		IRequest $request,
		private Acl $acl,
		private PollService $pollService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get list of polls
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
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
	 * get poll
	 * @param $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
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
	 * get acl for poll
	 * @param $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getAcl(): JSONResponse {
		try {
			return new JSONResponse(['acl' => $this->acl], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Add poll
	 * @param string $title Poll title
	 * @param string $type Poll type ('datePoll', 'textPoll')
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function add(string $type, string $title): JSONResponse {
		try {
			return new JSONResponse(['poll' => $this->pollService->add($type, $title)], Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Update poll configuration
	 * @param int $pollId Poll id
	 * @param array $pollConfiguration poll config
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function update(int $pollId, array $pollConfiguration): JSONResponse {
		try {
			return new JSONResponse([
				'poll' => $this->pollService->update($pollId, $pollConfiguration),
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
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
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
	 * Close poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function close(int $pollId): JSONResponse {
		try {
			return new JSONResponse(['poll' => $this->pollService->close($pollId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Reopen poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function reopen(int $pollId): JSONResponse {
		try {
			return new JSONResponse(['poll' => $this->pollService->reopen($pollId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Delete poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
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
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
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
	 * @param string $sourceUser User to transfer polls from
	 * @param string $destinationUser User to transfer polls to
	 */
	#[CORS]
	#[NoCSRFRequired]
	public function transferPolls(string $sourceUser, string $destinationUser): JSONResponse {
		try {
			return new JSONResponse(['transferred' => $this->pollService->transferPolls($sourceUser, $destinationUser)], Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Transfer singe poll to another user (change owner of poll)
	 * @param int $pollId Poll to transfer
	 * @param string $destinationUser User to transfer the poll to
	 */
	#[CORS]
	#[NoCSRFRequired]
	public function transferPoll(int $pollId, string $destinationUser): JSONResponse {
		try {
			return new JSONResponse(['transferred' => $this->pollService->transferPoll($pollId, $destinationUser)], Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Collect email addresses from particitipants
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
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
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function enum(): JSONResponse {
		return new JSONResponse($this->pollService->getValidEnum(), Http::STATUS_OK);
	}
}
