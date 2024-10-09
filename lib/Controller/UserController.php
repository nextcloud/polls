<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Model\Acl;
use OCA\Polls\Service\CalendarService;
use OCA\Polls\Service\PreferencesService;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class UserController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private PreferencesService $preferencesService,
		private CalendarService $calendarService,
		private Acl $acl,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Read all preferences
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/preferences')]
	public function getPreferences(): JSONResponse {
		return $this->response(fn () => $this->preferencesService->get());
	}

	/**
	 * Write preferences
	 * @param array $preferences
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/preferences')]
	public function writePreferences(array $preferences): JSONResponse {
		return $this->response(fn () => $this->preferencesService->write($preferences));
	}
	
	/**
	 * get session information
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/session')]
	public function getSession(): JSONResponse {
		return $this->response(fn () => [
			'token' => $this->request->getParam('token'),
			'currentUser' => $this->acl->getCurrentUser(),
			'appPermissions' => $this->acl->getPermissionsArray(),
			'appSettings' => $this->acl->getAppSettings(),
			'share' => null,
		]);
	}
	
	/**
	 * Read all calendars
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/calendars')]
	public function getCalendars(): JSONResponse {
		return $this->response(fn () => [
			'calendars' => $this->calendarService->getCalendars(),
		]);
	}
	
	/**
	 * get acl for user
	 * @deprecated 8.0.0 Use getSession instead
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/acl')]
	public function getAcl(): JSONResponse {
		return $this->response(fn () => [
			'acl' => $this->acl,
		]);
	}
}
