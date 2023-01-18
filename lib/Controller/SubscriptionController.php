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

use OCA\Polls\Model\Acl;
use OCA\Polls\Service\SubscriptionService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ISession;

class SubscriptionController extends BaseController {
	public function __construct(
		string $appName,
		ISession $session,
		IRequest $request,
		private Acl $acl,
		private SubscriptionService $subscriptionService
	) {
		parent::__construct($appName, $request, $session);
	}

	/**
	 * Get subscription status
	 * @NoAdminRequired
	 */
	public function get(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->get($this->acl->setPollId($pollId))
		]);
	}

	/**
	 * subscribe
	 * @NoAdminRequired
	 */
	public function subscribe(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(true, $this->acl->setPollId($pollId))
		]);
	}

	/**
	 * Unsubscribe
	 * @NoAdminRequired
	 */
	public function unsubscribe(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(false, $this->acl->setPollId($pollId))
		]);
	}
}
