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

use OCP\IRequest;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\MailService;

class ShareApiController extends ApiController {
	use ResponseHandle;

	/** @var ShareService */
	private $shareService;

	/** @var MailService */
	private $mailService;

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
	 */
	public function list(int $pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return ['shares' => $this->shareService->list($pollId)];
		});
	}

	/**
	 * Get share by token
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function get(string $token): DataResponse {
		return $this->response(function () use ($token) {
			return ['share' => $this->shareService->get($token)];
		});
	}

	/**
	 * Add share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function add(int $pollId, string $type, string $userId = ''): DataResponse {
		return $this->responseCreate(function () use ($pollId, $type, $userId) {
			return ['share' => $this->shareService->add($pollId, $type, $userId)];
		});
	}

	/**
	 * Delete share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function delete(string $token): DataResponse {
		return $this->responseDeleteTolerant(function () use ($token) {
			return ['share' => $this->shareService->delete($token)];
		});
	}

	/**
	 * Sent invitation mails for a share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function sendInvitation(string $token): DataResponse {
		return $this->response(function () use ($token) {
			$sentResult = $this->mailService->sendInvitation($token);
			$share = $this->shareService->get($token);
			return [
				'share' => $share,
				'sentResult' => $sentResult
			];
		});
	}
}
