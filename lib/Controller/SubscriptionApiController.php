<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\SubscriptionService;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class SubscriptionApiController extends BaseApiV2Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private SubscriptionService $subscriptionService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get subscription status
	 * @param int $pollId poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/{apiVersion}/poll/{pollId}/subscription', requirements: ['apiVersion' => '(v2)'])]
	public function get(int $pollId): DataResponse {
		return $this->response(fn () => [
			'pollId' => $pollId,
			'subscribed' => $this->subscriptionService->get($pollId),
		]);
	}

	/**
	 * Subscribe to poll
	 * @param int $pollId poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/{apiVersion}/poll/{pollId}/subscription', requirements: ['apiVersion' => '(v2)'])]
	public function subscribe(int $pollId): DataResponse {
		return $this->response(fn () => [
			'pollId' => $pollId,
			'subscribed' => $this->subscriptionService->set(true, $pollId),
		]);
	}

	/**
	 * Unsubscribe from poll
	 * @param int $pollId poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/{apiVersion}/poll/{pollId}/subscription', requirements: ['apiVersion' => '(v2)'])]
	public function unsubscribe(int $pollId): DataResponse {
		return $this->response(fn () => [
			'pollId' => $pollId,
			'subscribed' => $this->subscriptionService->set(false, $pollId),
		]);
	}
}
