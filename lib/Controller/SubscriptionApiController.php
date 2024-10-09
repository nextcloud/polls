<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Service\SubscriptionService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class SubscriptionApiController extends BaseApiController {
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
	#[FrontpageRoute(verb: 'GET', url: '/api/v1/poll/{pollId}/subscription')]
	public function get(int $pollId): JSONResponse {
		try {
			return new JSONResponse([
				'pollId' => $pollId,
				'subscribed' => $this->subscriptionService->get($pollId),
			], Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Subscribe to poll
	 * @param int $pollId poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'POST', url: '/api/v1/poll/{pollId}/subscription')]
	public function subscribe(int $pollId): JSONResponse {
		try {
			$this->subscriptionService->set(true, $pollId);
			return new JSONResponse([
				'pollId' => $pollId,
				'subscribed' => $this->subscriptionService->get($pollId),
			], Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Unsubscribe from poll
	 * @param int $pollId poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/api/v1/poll/{pollId}/subscription')]
	public function unsubscribe(int $pollId): JSONResponse {
		try {
			$this->subscriptionService->set(false, $pollId);
			return new JSONResponse([
				'pollId' => $pollId,
				'subscribed' => $this->subscriptionService->get($pollId),
			], Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}
}
