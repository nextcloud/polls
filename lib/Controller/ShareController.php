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
use OCA\Polls\Exceptions\InvalidUsername;


use OCP\IRequest;
use OCP\ILogger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;



use OCA\Polls\Model\Acl;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\MailService;

class ShareController extends Controller {

	private $logger;
	private $shareService;
	private $mailService;
	private $userId;

	/**
	 * ShareController constructor.
	 * @param string $appName
	 * @param string $userId
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param MailService $mailService
	 * @param ShareService $shareService
	 */
	public function __construct(
		string $appName,
		$userId,
		IRequest $request,
		ILogger $logger,
		MailService $mailService,
		ShareService $shareService
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->userId = $userId;
		$this->shareService = $shareService;
		$this->mailService = $mailService;
	}

	/**
	 * Write a new share to the db and returns the new share as array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @param Array $share
	 * @return DataResponse
	 */
	 public function add($pollId, $type, $userId = '', $userEmail = '') {
 		try {
 			return new DataResponse(['share' => $this->shareService->add($pollId, $type, $userId, $userEmail)], Http::STATUS_CREATED);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
		}
	}

	/**
	 * createPersonalShare
	 * Write a new share to the db and returns the new share as array
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $token
	 * @param string $userName
	 * @return DataResponse
	 */
	public function createPersonalShare($token, $userName) {

		try {
			return new DataResponse($this->shareService->createPersonalShare($token, $userName), Http::STATUS_CREATED);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (InvalidUsername $e) {
			return new DataResponse(['error' => $userName . ' is not valid'], Http::STATUS_CONFLICT);
		} catch (DoesNotExistException $e) {
			// return forbidden in all not catched error cases
			return new DataResponse($e, Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * SendInvitation
	 * Sent invitation mails for a share
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function sendInvitation($token) {
		try {
			$sentResult = $this->mailService->sendInvitationMail($token);
			$share = $this->shareService->get($token);
			return new DataResponse(['share' => $share, 'sentResult' => $sentResult], Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * remove
	 * remove share
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Share $share
	 * @return DataResponse
	 */

	public function delete($share) {
		try {
			return new DataResponse(array(
				'action' => 'deleted',
				'shareId' => $this->shareService->remove($share['token'])->getId()
			), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}
}
