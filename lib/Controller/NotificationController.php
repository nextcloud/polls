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

use Exeption;
use OCP\AppFramework\Db\DoesNotExistException;

use OCP\IRequest;
use OCP\ILogger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;




use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Notification;
use OCA\Polls\Db\NotificationMapper;

class NotificationController extends Controller {

	private $mapper;
	private $userId;
	private $logger;

	private $eventMapper;


	public function __construct(
		string $AppName,
		IRequest $request,
		ILogger $logger,
		NotificationMapper $mapper,
		$UserId,
		EventMapper $eventMapper
	) {
		parent::__construct($AppName, $request);
		$this->mapper = $mapper;
		$this->userId = $UserId;
		$this->logger = $logger;
		$this->eventMapper = $eventMapper;
	}



	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Integer $pollId
	 * @return DataResponse
	 */
	public function get(Int $pollId) {

		if ($this->userId === '') {
			return new DataResponse('No notification found', Http::STATUS_NOT_FOUND);
		}

		try {
			$notification = $this->mapper->findByUserAndPoll($pollId, $this->userId);

			if (count($notification) > 0) {
				return new DataResponse(array(
					'id' => $notification[0]->getId(),
					'pollId' => $notification[0]->getPollId(),
					'userId' => $notification[0]->getUserId()
				), Http::STATUS_OK);
			} else {
				$this->logger->debug('no notication for user ' . $this->userId . ' and event ' . $pollId, ['app' => 'polls']);
				return new DataResponse(array(
					'id' => 0,
					'pollId' => $pollId,
					'userId' => $this->userId
				), Http::STATUS_NOT_FOUND);
			}

		} catch (DoesNotExistException $e) {
			$this->logger->debug($e, ['app' => 'polls']);
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * @param int $pollId
	 */
	public function set($pollId, $subscribed) {
		if ($subscribed) {
			$notification = new Notification();
			$notification->setPollId($pollId);
			$notification->setUserId($this->userId);
			$this->mapper->insert($notification);
			return true;
		} else {
			$this->mapper->unsubscribe($pollId, $this->userId);
			return false;
		}
	}

}
