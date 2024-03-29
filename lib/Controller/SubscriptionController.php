<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
		private SubscriptionService $subscriptionService
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
