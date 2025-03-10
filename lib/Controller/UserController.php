<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Service\CalendarService;
use OCA\Polls\Service\PreferencesService;
use OCA\Polls\UserSession;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class UserController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private AppSettings $appSettings,
		private CalendarService $calendarService,
		private PreferencesService $preferencesService,
		private UserSession $userSession,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Write preferences
	 * @param array $preferences
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'POST', url: '/preferences')]
	public function writePreferences(array $preferences): JSONResponse {
		return $this->response(fn () => $this->preferencesService->write($preferences));
	}

	/**
	 * Read all preferences
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/preferences')]
	public function getPreferences(): JSONResponse {
		return $this->response(fn() => $this->preferencesService->get());
	}

	/**
	 * get session information
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/session')]
	public function getSession(): JSONResponse {
		return $this->response(fn () => [
			'token' => $this->request->getParam('token'),
			'currentUser' => $this->userSession->getUser(),
			'appPermissions' => $this->appSettings->getPermissionsArray(),
			'appSettings' => $this->appSettings->getAppSettings(),
			'share' => null,
		]);
	}

	/**
	 * Read all calendars
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/calendars')]
	public function getCalendars(): JSONResponse {
		return $this->response(fn () => [
			'calendars' => $this->calendarService->getCalendars(),
		]);
	}
}
