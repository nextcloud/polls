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
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-api
 */
class BaseApiV2Controller extends OCSController
{
	public function __construct(
		string   $appName,
		IRequest $request,
		string   $corsMethods = 'PUT, POST, GET, DELETE',
		string   $corsAllowedHeaders = 'Authorization, Content-Type, Accept',
		int      $corsMaxAge = 1728000,
	)
	{
		parent::__construct($appName, $request, $corsMethods, $corsAllowedHeaders, $corsMaxAge);
	}

	/**
	 * response
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function response(Closure $callback): DataResponse
	{
		return $this->handleResponse($callback, Http::STATUS_OK);
	}

	/**
	 * response
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function responseLong(Closure $callback): DataResponse
	{
		return $this->handleResponse($callback, Http::STATUS_OK);
	}

	/**
	 * responseCreate
	 * @param Closure $callback Callback function
	 */
	#[NoAdminRequired]
	protected function responseCreate(Closure $callback): DataResponse
	{
		return $this->handleResponse($callback, Http::STATUS_CREATED);
	}

	private function handleResponse(
		Closure $callback,
		int     $successStatus = Http::STATUS_OK
	): DataResponse
	{
		try {
			return new DataResponse($callback(), $successStatus);
		} catch (NoUpdatesException $e) {
			return new DataResponse([], Http::STATUS_NOT_MODIFIED);
		} catch (DoesNotExistException $e) {
			throw new OCSNotFoundException($e->getMessage());
		} catch (Exception $e) {
			throw new OCSBadRequestException($e->getMessage());
		}
	}
}
