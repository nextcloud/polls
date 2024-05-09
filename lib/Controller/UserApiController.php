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
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class UserApiController extends BaseApiController {
	public function __construct(
		string $appName,
		IRequest $request,
		private PreferencesService $preferencesService,
		private Acl $acl,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get user preferences
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getPreferences(): JSONResponse {
		return $this->response(fn () => $this->preferencesService->get());
	}
	
	/**
	 * Get user preferences
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function writePreferences(array $preferences): JSONResponse {
		return $this->response(fn () => $this->preferencesService->write($preferences));
	}
	
	/**
	 * get acl for poll
	 * @param $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getAcl(): JSONResponse {
		return $this->response(fn () => ['acl' => $this->acl]);
	}
}
