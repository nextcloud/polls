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

use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Service\SubscriptionService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\AppFramework\Http;

class SubscriptionApiController extends BaseApiController {
	/** @var SubscriptionService */
	private $subscriptionService;

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
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function get(int $pollId): JSONResponse {
		try {
			$this->subscriptionService->get($pollId, '');
			return new JSONResponse(['status' => 'Subscribed to poll ' . $pollId], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['status' => 'Not subscribed to poll ' . $pollId], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Subscribe to poll
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function subscribe(int $pollId): JSONResponse {
		try {
			$this->subscriptionService->set(true, $pollId, '');
			return new JSONResponse(['status' => 'Subscribed to poll ' . $pollId], Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Unsubscribe from poll
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function unsubscribe(int $pollId): JSONResponse {
		try {
			$this->subscriptionService->set(false, $pollId, '');
			return new JSONResponse(['status' => 'Unsubscribed from poll ' . $pollId], Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}
}
