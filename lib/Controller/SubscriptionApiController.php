<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\SubscriptionService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class SubscriptionApiController extends BaseApiV2OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private SubscriptionService $subscriptionService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get subscription status for a poll
	 * 200: Returns subscription status
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{pollId: int, subscribed: bool}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/subscription')]
	public function get(int $pollId): DataResponse {
		return $this->response(fn () => [
			'pollId' => $pollId,
			'subscribed' => $this->subscriptionService->get($pollId),
		]);
	}

	/**
	 * Subscribe to poll
	 * 200: Subscribed to poll
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{pollId: int, subscribed: bool}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}/subscription')]
	public function subscribe(int $pollId): DataResponse {
		return $this->response(fn () => [
			'pollId' => $pollId,
			'subscribed' => $this->subscriptionService->set(true, $pollId),
		]);
	}

	/**
	 * Unsubscribe from poll
	 * 200: Unsubscribed from poll
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{pollId: int, subscribed: bool}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1.0/poll/{pollId}/subscription')]
	public function unsubscribe(int $pollId): DataResponse {
		return $this->response(fn () => [
			'pollId' => $pollId,
			'subscribed' => $this->subscriptionService->set(false, $pollId),
		]);
	}
}
