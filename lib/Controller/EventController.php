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

use OCP\IRequest;
use OCP\ILogger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Service\EventService;



class EventController extends Controller {

	private $userId;
	private $mapper;
	private $logger;
	private $groupManager;
	private $userManager;
	private $eventService;

	/**
	 * CommentController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param EventMapper $mapper
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param EventService $eventService
	 */

	public function __construct(
		string $appName,
		$UserId,
		IRequest $request,
		ILogger $logger,
		EventMapper $mapper,
		IGroupManager $groupManager,
		IUserManager $userManager,
		EventService $eventService
	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->eventService = $eventService;
	}

	/**
	 * Get all polls
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return DataResponse
	 */

	public function list() {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$events = $this->mapper->findAll();
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return new DataResponse($events, Http::STATUS_OK);

	}

	 /**
	  * Read an entire poll based on poll id
	  * @NoAdminRequired
	  * @NoCSRFRequired
	  * @param integer $pollId
	  * @return array
	  */
 	public function get($pollId) {
		$data = array();

 		try {
 			$event = $this->mapper->find($pollId);
 		} catch (DoesNotExistException $e) {
			$this->logger->info('Poll ' . $pollId . ' not found!', ['app' => 'polls']);
			$this->logger->debug($e, ['app' => 'polls']);
			$data['poll'] = ['result' => 'notFound'];
 		}

		if ($event->getType() == 0) {
			$pollType = 'datePoll';
		} else {
			$pollType = 'textPoll';
		}

		$accessType = $event->getAccess();
		if (!strpos('|public|hidden|registered', $accessType)) {
			$accessType = 'select';
		}
		if ($event->getExpire() == null) {
			$expired = false;
			$expiration = false;
		} else {
			$expired = time() > strtotime($event->getExpire());
			$expiration = true;
		}

		return (object) [
			'id' => $event->getId(),
			'type' => $pollType,
			'title' => $event->getTitle(),
			'description' => $event->getDescription(),
			'owner' => $event->getOwner(),
			'ownerDisplayName' => $this->userManager->get($event->getOwner())->getDisplayName(),
			'created' => $event->getCreated(),
			'access' => $accessType,
			'expiration' => $expiration,
			'expired' => $expired,
			'expirationDate' => $event->getExpire(),
			'isAnonymous' => boolval($event->getIsAnonymous()),
			'fullAnonymous' => boolval($event->getFullAnonymous()),
			'allowMaybe' => boolval($event->getAllowMaybe())
		];
 	}




	/**
	 * Write poll (create/update)
	 * @NoAdminRequired
	 * @param Array $event
	 * @param Array $options
	 * @param Array  $shares
	 * @param string $mode
	 * @return DataResponse
	 */
	public function add($event) {
		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		$NewEvent = new Event();

		// Set the configuration options entered by the user
		$NewEvent->setTitle($event['title']);
		$NewEvent->setDescription($event['description']);

		if ($event['type'] === 'datePoll') {
			$NewEvent->setType(0);
		} elseif ($event['type'] === 'textPoll') {
			$NewEvent->setType(1);
		}

		$NewEvent->setAccess('hidden');

		$NewEvent->setIsAnonymous(0);
		$NewEvent->setFullAnonymous(0);
		$NewEvent->setAllowMaybe(0);

		$NewEvent->setOwner($this->userId);
		$NewEvent->setCreated(date('Y-m-d H:i:s'));
		$NewEvent = $this->mapper->insert($NewEvent);

		return new DataResponse($this->get($NewEvent->getId()), Http::STATUS_OK);

	}

	/**
	 * Write poll (create/update)
	 * @NoAdminRequired
	 * @param Array $event
	 * @param Array $options
	 * @param Array  $shares
	 * @param string $mode
	 * @return DataResponse
	 */

	public function write($event, $mode) {
		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$adminAccess = $this->groupManager->isAdmin($this->userId);
		}

		$this->logger->alert(json_encode($event));

		$NewEvent = new Event();

		// Set the configuration options entered by the user
		$NewEvent->setTitle($event['title']);
		$NewEvent->setDescription($event['description']);

		$NewEvent->setIsAnonymous(intval($event['isAnonymous']));
		$NewEvent->setFullAnonymous(intval($event['fullAnonymous']));
		$NewEvent->setAllowMaybe(intval($event['allowMaybe']));

		if ($event['access'] === 'select') {
			// $shareAccess = '';
			// foreach ($shares as $shareElement) {
			// 	if ($shareElement['type'] === 'user') {
			// 		$shareAccess = $shareAccess . 'user_' . $shareElement['id'] . ';';
			// 	} elseif ($shareElement['type'] === 'group') {
			// 		$shareAccess = $shareAccess . 'group_' . $shareElement['id'] . ';';
			// 	}
			// }
			// $NewEvent->setAccess(rtrim($shareAccess, ';'));
		} else {
			$NewEvent->setAccess($event['access']);
		}

		if ($event['expiration']) {
			$NewEvent->setExpire(date('Y-m-d H:i:s', strtotime($event['expirationDate'])));
		} else {
			$NewEvent->setExpire(null);
		}

		if ($event['type'] === 'datePoll') {
			$NewEvent->setType(0);
		} elseif ($event['type'] === 'textPoll') {
			$NewEvent->setType(1);
		}

		if ($mode === 'edit') {
			// Edit existing poll
			$oldEvent = $this->mapper->find($event['id']);

			// Check if current user is allowed to edit existing poll
			if ($oldEvent->getOwner() !== $this->userId && !$adminAccess) {
				// If current user is not owner of existing poll deny access
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

			// else take owner, and id of existing poll
			$NewEvent->setOwner($oldEvent->getOwner());
			$NewEvent->setId($oldEvent->getId());
			try {
				$this->mapper->update($NewEvent);
				$this->logger->debug('updating', ['app' => 'polls']);

			} catch (Exception $e) {
				$this->logger->alert('Poll ' . $oldEvent['id'] . ' not found!', ['app' => 'polls']);
			}

		} elseif ($mode === 'create') {
			// Create new poll
			// Define current user as owner, set new creation date
			$NewEvent->setOwner($this->userId);
			$NewEvent->setCreated(date('Y-m-d H:i:s'));
			$NewEvent = $this->mapper->insert($NewEvent);
		}
		return new DataResponse($this->get($NewEvent->getId()), Http::STATUS_OK);
	}
}
