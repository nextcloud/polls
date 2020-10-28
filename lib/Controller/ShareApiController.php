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
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCP\IRequest;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\MailService;

class ShareApiController extends ApiController {

	/** @var ShareService */
	private $shareService;

	/** @var MailService */
	private $mailService;

	/**
	 * ShareApiController constructor
	 * @param string $appName
	 * @param IRequest $request
	 * @param MailService $mailService
	 * @param ShareService $shareService
	 */
	public function __construct(
		string $appName,
		IRequest $request,
		MailService $mailService,
		ShareService $shareService
	) {
		parent::__construct($appName,
			$request,
			'POST, PUT, GET, DELETE',
			'Authorization, Content-Type, Accept',
			1728000);
		$this->shareService = $shareService;
		$this->mailService = $mailService;
	}

	/**
	 * Read all shares of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @return DataResponse
	 */
	public function list($pollId) {
		try {
			return new DataResponse(['shares' => $this->shareService->list($pollId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'No shares for poll with id ' . $pollId . ' not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Get share by token
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function get($token) {
		try {
			return new DataResponse(['share' => $this->shareService->get($token)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Token ' . $token . ' not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Add share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @param string $type
	 * @param string $userId
	 * @param string $userEmail
	 * @return DataResponse
	 */
	public function add($pollId, $type, $userId = '') {
		try {
			return new DataResponse(['share' => $this->shareService->add($pollId, $type, $userId)], Http::STATUS_CREATED);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (Exception $e) {
			return new DataResponse(['error' => $e], Http::STATUS_CONFLICT);
		}
	}

	/**
	 * Delete share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param string $token
	 * @return DataResponse
	 */

	public function delete($token) {
		try {
			return new DataResponse(['share' => $this->shareService->delete($token)], Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Sent invitation mails for a share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function sendInvitation($token) {
		try {
			return new DataResponse($this->mailService->sendInvitationMail($token), Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse(['error' => $e], Http::STATUS_CONFLICT);
		}
	}
}
