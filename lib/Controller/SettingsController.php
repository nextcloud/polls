<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\SettingsService;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class SettingsController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private SettingsService $settingsService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Read app settings
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/settings/app')]
	public function getAppSettings(): JSONResponse {
		return $this->response(fn () => ['appSettings' => $this->settingsService->getAppSettings()]);
	}

	/**
	 * Write app settings
	 * @param array $appSettings Settings as array
	 */
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'POST', url: '/settings/app')]
	public function writeAppSettings(array $appSettings): JSONResponse {
		$this->settingsService->writeAppSettings($appSettings);
		return $this->response(fn () => ['appSettings' => $this->settingsService->getAppSettings()]);
	}
}
