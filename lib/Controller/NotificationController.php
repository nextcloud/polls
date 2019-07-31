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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Db\DoesNotExistException;

use OCP\ILogger;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Notification;
use OCA\Polls\Db\NotificationMapper;



class NotificationController extends Controller {

	private $logger;
	private $userManager;
	private $eventMapper;
	private $notificationMapper;

	/**
	 * PageController constructor.
	 * @param string $appName
	 * @param ILogger $logger
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param string $userId
	 * @param EventMapper $eventMapper
	 * @param NotificationMapper $commentMapper
	 */
	public function __construct(
		$appName,
		ILogger $logger,
		IRequest $request,
		IUserManager $userManager,
		$userId,
		EventMapper $eventMapper,
		NotificationMapper $commentMapper
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->userId = $userId;
		$this->userManager = $userManager;
		$this->eventMapper = $eventMapper;
		$this->notificationMapper = $commentMapper;
	}



	/**
	 * Read an entire poll based on the poll id or hash
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param String $pollIdOrHash poll id or hash
	 * @return DataResponse
	 */
	public function get($pollIdOrHash) {

		if (!\OC::$server->getUserSession()->getUser() instanceof IUser) {
			$currentUser = '';
		} else {
			$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		}

		$data = array();

		try {
			if (is_numeric($pollIdOrHash)) {
				$event = $this->eventMapper->find($pollIdOrHash);
				$result = 'foundById';
			} else {
				$event = $this->eventMapper->findByHash($pollIdOrHash);
				$result = 'foundByHash';
			}

			$notification = $this->notificationMapper->findByUserAndPoll($event->getId(), $currentUser);

			if (count($notification) > 0) {
				return new DataResponse(array(
					'id' => $notification[0]->getId(),
					'pollID' => $notification[0]->getPollId(),
					'userId' => $notification[0]->getUserId()
				), Http::STATUS_OK);

			} else {
				return new DataResponse('No notificatiopn found', Http::STATUS_NOT_FOUND);
			}

		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => 'polls']);
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * @param int $pollId
	 */
	public function set($pollId, $subscribed) {
		$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		if ($subscribed) {
			$notification = new Notification();
			$notification->setPollId($pollId);
			$notification->setUserId($currentUser);
			$this->notificationMapper->insert($notification);
			return true;
		} else {
			$this->notificationMapper->unsubscribe($pollId, $currentUser);
			return false;
		}
	}

}
