<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\SubscriptionService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class SubscriptionController extends BaseController {
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
	#[NoAdminRequired]
	public function get(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->get($pollId)
		]);
	}

	/**
	 * Subscribe
	 * @param int $pollId poll id
	 */
	#[NoAdminRequired]
	public function subscribe(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(true, $pollId)
		]);
	}

	/**
	 * Unsubscribe
	 * @param int $pollId poll id
	 */
	#[NoAdminRequired]
	public function unsubscribe(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(false, $pollId)
		]);
	}
}
