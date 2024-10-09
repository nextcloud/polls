<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use Closure;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Exceptions\NoUpdatesException;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class BaseApiV1Controller extends ApiController {
	public function __construct(
		string $appName,
		IRequest $request,
		string $corsMethods = 'PUT, POST, GET, DELETE',
		string $corsAllowedHeaders = 'Authorization, Content-Type, Accept',
		int $corsMaxAge = 1728000,
	) {
		parent::__construct($appName, $request, $corsMethods, $corsAllowedHeaders, $corsMaxAge);
	}

	/**
	 * response
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function response(Closure $callback): JSONResponse {
		try {
			return new JSONResponse($callback(), Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * response
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function responseLong(Closure $callback): JSONResponse {
		try {
			return new JSONResponse($callback(), Http::STATUS_OK);
		} catch (NoUpdatesException $e) {
			return new JSONResponse([], Http::STATUS_NOT_MODIFIED);
		}
	}

	/**
	 * responseCreate
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function responseCreate(Closure $callback): JSONResponse {
		try {
			return new JSONResponse($callback(), Http::STATUS_CREATED);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}
}
