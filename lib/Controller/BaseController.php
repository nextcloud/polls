<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use Closure;
use OCA\Polls\Exceptions\Exception;
use OCP\AppFramework\Controller;
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
	 * @param int $successStatus HTTP status code for success
	 */
	#[NoAdminRequired]
	protected function response(
		Closure $callback,
		int $successStatus = Http::STATUS_OK,
	): JSONResponse {
		try {
			return new JSONResponse($callback(), $successStatus);
		} catch (Exception $e) {

			if ($e->getStatus() === Http::STATUS_NOT_MODIFIED) {
				return new JSONResponse(statusCode: $e->getStatus());
			}

			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

}
