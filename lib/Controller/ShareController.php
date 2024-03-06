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

use OCA\Polls\Db\Share;
use OCA\Polls\Service\ShareService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class ShareController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private ShareService $shareService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * List shares
	 * @param int $pollId poll id
	 */
	#[NoAdminRequired]
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => ['shares' => $this->shareService->list($pollId)]);
	}

	/**
	 * Add share
	 * @param int $pollId poll id
	 * @param string $type Share type
	 * @param string $userId User id
	 * @param string $displayName Displayname of user
	 * @param string $emailAddress Email address of user
	 */
	#[NoAdminRequired]
	public function add(int $pollId, string $type, string $userId = '', string $displayName = '', string $emailAddress = ''): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->add($pollId, $type, $userId, $displayName, $emailAddress)]);
	}

	/**
	 * Change the contraints for email addresses in public polls
	 * @param string $token Share token
	 * @param string $value new value
	 */
	#[NoAdminRequired]
	public function setPublicPollEmail(string $token, string $value): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->setPublicPollEmail($token, $value)]);
	}

	/**
	 * Change Label of a public share
	 * @param string $token Share token
	 * @param string $label new label of oublic poll
	 */
	#[NoAdminRequired]
	public function setLabel(string $token, string $label = ''): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setLabel($label, $token)
		]);
	}

	/**
	 * Convert poll admin to user
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	public function adminToUser(string $token): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->setType($token, Share::TYPE_USER)]);
	}

	/**
	 * Convert user to poll admin
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	public function userToAdmin(string $token): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->setType($token, Share::TYPE_ADMIN)]);
	}

	/**
	 * Set email address
	 * @param string $token Share token
	 * @param string $emailAddress Email address
	 */
	#[NoAdminRequired]
	public function setEmailAddress(string $token, string $emailAddress = ''): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setEmailAddress($this->shareService->get($token),
				$emailAddress)
		]);
	}

	/**
	 * Delete share
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	public function delete(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->deleteByToken($token)]);
	}

	/**
	 * Restore deleted share
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	public function restore(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->deleteByToken($token, restore: true)]);
	}

	/**
	 * Lock a share (read only)
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	public function lock(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->lockByToken($token)]);
	}

	/**
	 * Unlock share
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	public function unlock(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->lockByToken($token, unlock: true)]);
	}

	/**
	 * Send invitation mails for a share
	 * Additionally send notification via notifications
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	public function sendInvitation(string $token): JSONResponse {
		$share = $this->shareService->get($token);
		return $this->response(fn () => [
			'share' => $share,
			'sentResult' => $this->shareService->sendInvitation($share),
		]);
	}

	/**
	 * Send all invitation mails for a share and resolve groups
	 * Additionally send notification via notifications
	 * @param int $pollId poll id
	 */
	#[NoAdminRequired]
	public function sendAllInvitations(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $pollId,
			'sentResult' => $this->shareService->sendAllInvitations($pollId),
		]);
	}

	/**
	 * resolve contact group to individual shares
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	public function resolveGroup(string $token): JSONResponse {
		return $this->response(fn () => [
			'shares' => $this->shareService->resolveGroupByToken($token)
		]);
	}
}
