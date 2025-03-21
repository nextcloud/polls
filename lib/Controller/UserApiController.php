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
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class UserApiController extends BaseApiV2Controller {
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
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/preferences', requirements: ['apiVersion' => '(v2)'])]
	public function writePreferences(array $preferences): DataResponse {
		return $this->response(fn () => $this->preferencesService->write($preferences));
	}
	/**
	 * get user session
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/session', requirements: ['apiVersion' => '(v2)'])]
	public function getSession(): DataResponse {
		return $this->response(fn () => [
			'token' => $this->request->getParam('token'),
			'currentUser' => $this->userSession->getCurrentUser(),
			'appPermissions' => $this->appSettings->getPermissionsArray(),
			'appSettings' => $this->appSettings->getAppSettings(),
		]);
	}
}
