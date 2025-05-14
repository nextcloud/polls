<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\MailService;
use OCA\Polls\Service\ShareService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class ShareApiController extends BaseApiV2Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private MailService $mailService,
		private ShareService $shareService,
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
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/shares', requirements: ['apiVersion' => '(v2)'])]
	public function list(int $pollId): DataResponse {
		return $this->response(fn () => ['shares' => $this->shareService->list($pollId)]);
	}

	/**
	 * Get share by token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/share/{token}', requirements: ['apiVersion' => '(v2)'])]
	public function get(string $token): DataResponse {
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
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/poll/{pollId}/share/{type}', requirements: ['apiVersion' => '(v2)'])]
	public function add(int $pollId, string $type, string $userId = '', string $displayName = '', string $emailAddress = ''): DataResponse {
		return $this->response(fn () => ['share' => $this->shareService->add($pollId, $type, $userId, $displayName, $emailAddress)], Http::STATUS_CREATED);
	}

	/**
	 * Delete share
	 * @param string $token Share token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1.0/share/{token}', requirements: ['apiVersion' => '(v2)'])]
	public function delete(string $token): DataResponse {
		return $this->response(fn () => ['share' => $this->shareService->deleteByToken($token)]);
	}

	/**
	 * Restore deleted share
	 * @param string $token Share token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/share/{token}/restore', requirements: ['apiVersion' => '(v2)'])]
	public function restore(string $token): DataResponse {
		return $this->response(fn () => ['share' => $this->shareService->deleteByToken($token, restore: true)]);
	}

	/**
	 * Lock a share (read only)
	 * @param string $token Share token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/share/{token}/lock', requirements: ['apiVersion' => '(v2)'])]
	public function lock(string $token): DataResponse {
		return $this->response(fn () => ['share' => $this->shareService->lockByToken($token)]);
	}

	/**
	 * Unlock share
	 * @param string $token Share token
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/share/{token}/unlock', requirements: ['apiVersion' => '(v2)'])]
	public function unlock(string $token): DataResponse {
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
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/share/{token}/invite', requirements: ['apiVersion' => '(v2)'])]
	public function sendInvitation(string $token): DataResponse {
		$share = $this->shareService->get($token);
		return $this->response(fn () => [
			'share' => $share,
			'sentResult' => $this->mailService->sendInvitation($share),
		]);
	}
}
