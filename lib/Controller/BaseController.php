<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use Closure;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Exceptions\NoUpdatesException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class BaseController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * response
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function response(Closure $callback): JSONResponse {
		try {
			return new JSONResponse($callback());
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
			return new JSONResponse($callback());
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

	/**
	 * responseDeleteTolerant
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function responseDeleteTolerant(Closure $callback): JSONResponse {
		try {
			return new JSONResponse($callback());
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['message' => 'Not found, assume already deleted']);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}
}
