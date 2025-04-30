<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use Closure;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Exceptions\NoUpdatesException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class BasePublicController extends Controller {
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

	private function handleResponse(
    		Closure $callback,
    		int $successStatus,
    		string $exceptionClass,
    		int $fallbackStatus = null
    	): JSONResponse {
    		try {
    			return new JSONResponse($callback(), $successStatus);
    		} catch (\Throwable $e) {
    			if (is_a($e, $exceptionClass, true)) {
    				if ($fallbackStatus !== null && $e instanceof NoUpdatesException) {
    					return new JSONResponse([], $fallbackStatus);
    				}
    				if ($e instanceof Exception) {
    					return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
    				}
    			}
    			return new JSONResponse(['message' => 'Unexpected error'], Http::STATUS_INTERNAL_SERVER_ERROR);
    		}
    	}
}



