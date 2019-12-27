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
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCP\IRequest;
use OCP\ILogger;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\SubscriptionMapper;

class SubscriptionController extends Controller {

	private $userId;
	private $mapper;
	private $logger;

	/**
	 * SubscriptionController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param SubscriptionMapper $mapper
	 * @param IRequest $request
	 * @param ILogger $logger
	 */

	public function __construct(
		string $appName,
		$userId,
		SubscriptionMapper $mapper,
		IRequest $request,
		ILogger $logger

	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->mapper = $mapper;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {

		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$this->mapper->findByUserAndPoll($pollId, $this->userId);
		} catch (MultipleObjectsReturnedException $e) {
			// should not happen, but who knows
		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}
		return new DataResponse(null, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @param integer $pollId
	 */
	public function set($pollId, $subscribed) {
		if ($subscribed) {
			$subscription = new Subscription();
			$subscription->setPollId($pollId);
			$subscription->setUserId($this->userId);
			$this->mapper->insert($subscription);
			return true;
		} else {
			$this->mapper->unsubscribe($pollId, $this->userId);
			return false;
		}
	}
}
