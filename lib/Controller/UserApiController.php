<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Service\PreferencesService;
use OCA\Polls\UserSession;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 * @psalm-import-type PollsSession from \OCA\Polls\ResponseDefinitions
 */
class UserApiController extends BaseApiV2OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private PreferencesService $preferencesService,
		private UserSession $userSession,
		private AppSettings $appSettings,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Write user preferences
	 * 200: Preferences saved
	 * @param array<string, mixed> $preferences Preferences to save
	 * @return DataResponse<Http::STATUS_OK, array{preferences: array<string, mixed>}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/preferences')]
	public function writePreferences(array $preferences): DataResponse {
		return $this->response(fn () => $this->preferencesService->write($preferences));
	}

	/**
	 * Get current user session info
	 * 200: Returns session info
	 * @param string|null $token Share token for public poll sessions
	 * @return DataResponse<Http::STATUS_OK, PollsSession, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/session')]
	public function getSession(?string $token = null): DataResponse {
		return $this->response(fn () => [
			'token' => $token,
			'currentUser' => $this->userSession->getCurrentUser(),
			'appPermissions' => $this->appSettings->getPermissionsArray(),
			'appSettings' => $this->appSettings->getAppSettings(),
		]);
	}
}
