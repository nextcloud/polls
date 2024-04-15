<?php

declare(strict_types=1);
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

use OCA\Polls\Service\MailService;
use OCA\Polls\Service\ShareService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class ShareApiController extends BaseApiController {
	public function __construct(
		string $appName,
		IRequest $request,
		private MailService $mailService,
		private ShareService $shareService
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Read all shares of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => ['shares' => $this->shareService->list($pollId)]);
	}

	/**
	 * Get share by token
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function get(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->get($token)]);
	}

	/**
	 * Add share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function add(int $pollId, string $type, string $userId = ''): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->add($pollId, $type, $userId)]);
	}

	/**
	 * Delete share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function delete(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->delete(token: $token)]);
	}

	/**
	 * Delete share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function restore(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->delete(token: $token, restore: true)]);
	}

	/**
	 * Lock share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function lock(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->lock(token: $token)]);
	}

	/**
	 * Unlock share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function unlock(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->lock(token: $token, unlock: true)]);
	}

	/**
	 * Sent invitation mails for a share
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function sendInvitation(string $token): JSONResponse {
		$share = $this->shareService->get($token);
		return $this->response(fn () => [
			'share' => $share,
			'sentResult' => $this->mailService->sendInvitation($share),
		]);
	}
}
