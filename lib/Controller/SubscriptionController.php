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

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\SubscriptionService;

class SubscriptionController extends Controller {

	/** @var SubscriptionService */
	private $subscriptionService;

	/**
	 * SubscriptionController constructor.
	 * @param string $appName
	 * @param SubscriptionService $subscriptionService
	 * @param IRequest $request
	 */

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
	 * @PublicPage
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return DataResponse
	 * @throws DoesNotExistException
	 * @throws NotAuthorizedException
	 */
	public function get($pollId, $token) {
		try {
			return new DataResponse($this->subscriptionService->get($pollId, $token), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (DoesNotExistException $e) {
			return new DataResponse(['status' => 'Not subscribed'], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Switch subscription status
	 * @PublicPage
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param int $subscribed
	 * @return DataResponse
	 * @throws NotAuthorizedException
	 */
	public function set($pollId, $token, $subscribed) {
		try {
			return new DataResponse($this->subscriptionService->set($pollId, $token, $subscribed), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}
}
