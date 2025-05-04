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
		return $this->handleResponse($callback, Http::STATUS_OK, Exception::class);
	}

	/**
	 * response
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function responseLong(Closure $callback): JSONResponse {
		return $this->handleResponse($callback, Http::STATUS_OK, NoUpdatesException::class, Http::STATUS_NOT_MODIFIED);
	}

	/**
	 * responseCreate
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function responseCreate(Closure $callback): JSONResponse {
		return $this->handleResponse($callback, Http::STATUS_CREATED, Exception::class);
	}

	/**
	 * responseDeleteTolerant
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function responseDeleteTolerant(Closure $callback): JSONResponse {
		return $this->handleResponse(
			$callback,
			Http::STATUS_OK,
			DoesNotExistException::class,
			Http::STATUS_OK,
			'Not found, assume already deleted',
			Exception::class
		);
	}

	private function handleResponse(
		Closure $callback,
		int $successStatus,
		string $primaryException,
		int $fallbackStatus = null,
		string $fallbackMessage = null,
		string $secondaryException = null
	): JSONResponse {
		try {
			return new JSONResponse($callback(), $successStatus);
		} catch (\Throwable $e) {
			if (is_a($e, $primaryException, true)) {
				if ($fallbackStatus !== null) {
					return new JSONResponse(['message' => $fallbackMessage ?? ''], $fallbackStatus);
				}
				if ($e instanceof Exception) {
					return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
				}
			}

			if ($secondaryException !== null && is_a($e, $secondaryException, true) && $e instanceof Exception) {
				return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
			}

			throw $e;
		}
	}
}

