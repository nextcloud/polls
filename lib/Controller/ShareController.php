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
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\MailService;

class ShareController extends Controller {

	/** @var ShareService */
	private $shareService;

	/** @var MailService */
	private $mailService;

	/**
	 * ShareController constructor.
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
		parent::__construct($appName, $request);
		$this->shareService = $shareService;
		$this->mailService = $mailService;
	}

	/**
	 * Add share
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param int $pollId
	 * @param string $type
	 * @param string $userId
	 * @param string $userEmail
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
	 * Create a personal share from a public share
	 * or update an email share with the username
	 * @NoAdminRequired
	 * @PublicPage
	 * @param string $token
	 * @param string $userName
	 * @return DataResponse
	 */
	public function personal($token, $userName) {

		try {
			return new DataResponse($this->shareService->personal($token, $userName), Http::STATUS_CREATED);
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
	 * Delete share
	 * @NoAdminRequired
	 * @param string $token
	 * @return DataResponse
	 */

	public function delete($token) {
		try {
			return new DataResponse($this->shareService->delete($token), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Sent invitation mails for a share
	 * @NoAdminRequired
	 * @PublicPage
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
}
