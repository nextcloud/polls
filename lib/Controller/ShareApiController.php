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
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
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
	 * List shares
	 * @param int $pollId poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => ['shares' => $this->shareService->list($pollId)]);
	}

	/**
	 * Get share by token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function get(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->get($token)]);
	}

	/**
	 * Add share
	 * @param int $pollId poll id
	 * @param string $type Share type
	 * @param string $userId User id
	 * @param string $displayName Displayname of user
	 * @param string $emailAddress Email address of user
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function add(int $pollId, string $type, string $userId = '', string $displayName = '', string $emailAddress = ''): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->add($pollId, $type, $userId, $displayName, $emailAddress)]);
	}

	/**
	 * Delete share
	 * @param string $token Share token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function delete(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->deleteByToken($token)]);
	}

	/**
	 * Restore deleted share
	 * @param string $token Share token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function restore(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->deleteByToken($token, restore: true)]);
	}

	/**
	 * Lock a share (read only)
	 * @param string $token Share token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function lock(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->lockByToken($token)]);
	}

	/**
	 * Unlock share
	 * @param string $token Share token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function unlock(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->lockByToken($token, unlock: true)]);
	}

	/**
	 * Send invitation mails for a share
	 * Additionally send notification via notifications
	 * @param string $token Share token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function sendInvitation(string $token): JSONResponse {
		$share = $this->shareService->get($token);
		return $this->response(fn () => [
			'share' => $share,
			'sentResult' => $this->mailService->sendInvitation($share),
		]);
	}
}
