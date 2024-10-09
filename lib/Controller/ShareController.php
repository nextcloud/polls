<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Db\Share;
use OCA\Polls\Service\ShareService;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/poll/{pollId}/shares')]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'POST', url: '/poll/{pollId}/share')]
	public function add(int $pollId, string $type, string $userId = '', string $displayName = '', string $emailAddress = ''): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->add($pollId, $type, $userId, $displayName, $emailAddress)]);
	}

	/**
	 * Change the contraints for email addresses in public polls
	 * @param string $token Share token
	 * @param string $value new value
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'POST', url: '/share/{token}/publicpollemail/{value}')]
	public function setPublicPollEmail(string $token, string $value): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->setPublicPollEmail($token, $value)]);
	}

	/**
	 * Change Label of a public share
	 * @param string $token Share token
	 * @param string $label new label of public poll
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'PUT', url: '/share/{token}/setlabel')]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'PUT', url: '/share/{token}/user')]
	public function adminToUser(string $token): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->setType($token, Share::TYPE_USER)]);
	}

	/**
	 * Convert user to poll admin
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'PUT', url: '/share/{token}/admin')]
	public function userToAdmin(string $token): JSONResponse {
		return $this->responseCreate(fn () => ['share' => $this->shareService->setType($token, Share::TYPE_ADMIN)]);
	}

	/**
	 * Delete share
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'DELETE', url: '/share/{token}')]
	public function delete(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->deleteByToken($token)]);
	}

	/**
	 * Restore deleted share
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'PUT', url: '/share/{token}/restore')]
	public function restore(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->deleteByToken($token, restore: true)]);
	}

	/**
	 * Lock a share (read only)
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'PUT', url: '/share/{token}/lock')]
	public function lock(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->lockByToken($token)]);
	}

	/**
	 * Unlock share
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'PUT', url: '/share/{token}/unlock')]
	public function unlock(string $token): JSONResponse {
		return $this->response(fn () => ['share' => $this->shareService->lockByToken($token, unlock: true)]);
	}

	/**
	 * Send invitation mails for a share
	 * Additionally send notification via notifications
	 * @param string $token Share token
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'POST', url: '/share/{token}/invite')]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'PUT', url: '/poll/{pollId}/inviteAll')]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/share/{token}/resolve')]
	public function resolveGroup(string $token): JSONResponse {
		return $this->response(fn () => [
			'shares' => $this->shareService->resolveGroupByToken($token)
		]);
	}

	/**
	 * Set email address
	 * @param string $token Share token
	 * @param string $emailAddress Email address
	 * @deprecated 8.0.0 Use PUT /s/{token}/email/{emailAddress}
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'PUT', url: '/share/{token}/email')]
	public function setEmailAddress(string $token, string $emailAddress = ''): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setEmailAddress(
				$this->shareService->get($token),
				$emailAddress
			)
		]);
	}
}
