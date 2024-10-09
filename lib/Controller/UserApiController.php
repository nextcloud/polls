<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Model\Acl as Acl;
use OCA\Polls\Service\PreferencesService;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class UserApiController extends BaseApiController
{
	public function __construct(
		string $appName,
		IRequest $request,
		private PreferencesService $preferencesService,
		private Acl $acl,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Write user preferences
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'POST', url: '/api/v1/preferences')]
	public function writePreferences(array $preferences): JSONResponse
	{
		return $this->response(fn() => $this->preferencesService->write($preferences));
	}
	/**
	 * get user session
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/api/v1/session')]
	public function getSession(): JSONResponse
	{
		return new JSONResponse([
			'token' => $this->request->getParam('token'),
			'currentUser' => $this->acl->getCurrentUser(),
			'appPermissions' => $this->acl->getPermissionsArray(),
			'appSettings' => $this->acl->getAppSettings(),
		]);
	}

	/**
	 * Get user preferences
	 * @deprecated 8.0.0 Use getSession instead
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getPreferences(): JSONResponse
	{
		return $this->response(fn() => $this->preferencesService->get());
	}

	/**
	 * get acl
	 * @deprecated 8.0.0 Use getSession instead
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/api/v1/acl')]
	public function getAcl(): JSONResponse
	{
		return $this->response(fn() => ['acl' => $this->acl]);
	}
}
