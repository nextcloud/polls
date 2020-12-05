<?php
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

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\SubscriptionService;

class SubscriptionController extends Controller {

	/** @var SubscriptionService */
	private $subscriptionService;

	use ResponseHandle;

	public function __construct(
		string $appName,
		SubscriptionService $subscriptionService,
		IRequest $request
	) {
		parent::__construct($appName, $request);
		$this->subscriptionService = $subscriptionService;
	}

	/**
	 * Get subscription status
	 * @NoAdminRequired
	 */
	public function get($pollId = 0): DataResponse {
		return $this->response(function () use ($pollId) {
			return ['subscribed' => $this->subscriptionService->get($pollId)];
		});
	}

	/**
	 * subscribe
	 * @NoAdminRequired
	 */
	public function subscribe($pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return ['subscribed' => $this->subscriptionService->set($pollId, '', true)];
		});
	}

	/**
	 * Unsubscribe
	 * @NoAdminRequired
	 */
	public function unsubscribe($pollId): DataResponse {
		return $this->response(function () use ($pollId) {
			return ['subscribed' => $this->subscriptionService->set($pollId, '', false)];
		});
	}
}
