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

use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\Exception;

use OCP\IRequest;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\SubscriptionService;

class SubscriptionApiController extends ApiController {

	/** @var SubscriptionService */
	private $subscriptionService;

	public function __construct(
		string $appName,
		SubscriptionService $subscriptionService,
		IRequest $request

	) {
		parent::__construct($appName,
			$request,
			'PUT, GET, DELETE',
			'Authorization, Content-Type, Accept',
			1728000);
		$this->subscriptionService = $subscriptionService;
	}

	/**
	 * Get subscription status
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function get(int $pollId): DataResponse {
		try {
			$this->subscriptionService->get($pollId, '');
			return new DataResponse(['status' => 'Subscribed to poll ' . $pollId], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['status' => 'Not subscribed to poll ' . $pollId], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Subscribe to poll
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function subscribe(int $pollId): DataResponse {
		try {
			$this->subscriptionService->set(true, $pollId, '');
			return new DataResponse(['status' => 'Subscribed to poll ' . $pollId], Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Unsubscribe from poll
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function unsubscribe(int $pollId): DataResponse {
		try {
			$this->subscriptionService->set(false, $pollId, '');
			return new DataResponse(['status' => 'Unsubscribed from poll ' . $pollId], Http::STATUS_OK);
		} catch (Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}
}
