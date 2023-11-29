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

use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Model\Acl;
use OCA\Polls\Service\SubscriptionService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class SubscriptionApiController extends BaseApiController {
	public function __construct(
		string $appName,
		IRequest $request,
		private Acl $acl,
		private SubscriptionService $subscriptionService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get subscription status
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function get(int $pollId): JSONResponse {
		try {
			return new JSONResponse([
				'pollId' => $pollId,
				'subscribed' => $this->subscriptionService->get($this->acl->setPollId($pollId)),
			], Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Subscribe to poll
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function subscribe(int $pollId): JSONResponse {
		try {
			$this->subscriptionService->set(true, $this->acl->setPollId($pollId));
			return new JSONResponse([
				'pollId' => $pollId,
				'subscribed' => $this->subscriptionService->get($this->acl->setPollId($pollId)),
			], Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Unsubscribe from poll
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function unsubscribe(int $pollId): JSONResponse {
		try {
			$this->subscriptionService->set(false, $this->acl->setPollId($pollId));
			return new JSONResponse([
				'pollId' => $pollId,
				'subscribed' => $this->subscriptionService->get($this->acl->setPollId($pollId)),
			], Http::STATUS_OK);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}
}
