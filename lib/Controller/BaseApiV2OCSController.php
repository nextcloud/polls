<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Polls\Controller;

use Closure;
use OCA\Polls\Exceptions\Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-api
 * @psalm-import-type HttpStatusCode from \OCA\Polls\Types
 */
class BaseApiV2OCSController extends OCSController {
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
	 * @template TData of array
	 * @template TStatus of Http::STATUS_*
	 * @param Closure(): TData $callback
	 * @param TStatus $successStatus
	 * @return DataResponse<TStatus, TData, array{}>
	 */
	#[NoAdminRequired]
	protected function response(Closure $callback, int $successStatus = Http::STATUS_OK): DataResponse {
		try {
			return new DataResponse($callback(), $successStatus);

		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException($e->getMessage());

		} catch (Exception $e) {
			throw match ($e->getStatus()) {
				Http::STATUS_NOT_MODIFIED => new OCSException($e->getMessage(), Http::STATUS_NOT_MODIFIED),
				default => new OCSBadRequestException($e->getMessage()),
			};
		}
	}
}
