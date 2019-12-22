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
use OCA\Polls\Service\LogService;
use OCA\Polls\Model\Acl;

class EventController extends Controller {

	private $userId;
	private $mapper;
	private $logger;
	private $groupManager;
	private $userManager;
	private $eventService;
	private $event;
	private $logService;
	private $acl;

	/**
	 * CommentController constructor.
	 * @param string $appName
	 * @param $userId
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param EventMapper $mapper
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param EventService $eventService
	 * @param LogService $logService
	 * @param Acl $acl
	 */

	public function __construct(
		string $appName,
		$userId,
		IRequest $request,
		ILogger $logger,
		EventMapper $mapper,
		Event $event,
		IGroupManager $groupManager,
		IUserManager $userManager,
		EventService $eventService,
		LogService $logService,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->mapper = $mapper;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->eventService = $eventService;
		$this->event = $event;
		$this->logService = $logService;
		$this->acl = $acl;
	}

	/**
	 * Get all polls
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return DataResponse
	 */

	public function list() {
		$events = [];
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			try {

				$events = array_filter($this->mapper->findAll(), function($item) {
					if ($this->acl->setPollId($item->getId())->getAllowView()) {
						return true;
					} else {
						return false;
					}
    			});
			} catch (DoesNotExistException $e) {
				$events = [];
				// return new DataResponse($e, Http::STATUS_NOT_FOUND);
			}
		}
		return new DataResponse($events, Http::STATUS_OK);
	}

	/**
	 * Read an entire poll based on poll id
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param integer $pollId
	 * @return array
	 */
 	public function get($pollId) {

 		try {
			if (!$this->acl->getFoundByToken()) {
				$this->acl->setPollId($pollId);
			}

			$this->event = $this->mapper->find($pollId);

		} catch (DoesNotExistException $e) {
			$this->logger->info('Poll ' . $pollId . ' not found!', ['app' => 'polls']);
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
 		}

		if ($this->event->getType() == 0) {
			$pollType = 'datePoll';
		} else {
			$pollType = 'textPoll';
		}

		// TODO: add migration for this
		if ($this->event->getAccess() === 'public' || $this->event->getAccess() === 'registered') {
			$this->event->setAccess('public');
		} else {
			$this->event->setAccess('hidden');
		}

		return new DataResponse((object) [
			'id' => $this->event->getId(),
			'type' => $pollType,
			'title' => $this->event->getTitle(),
			'description' => $this->event->getDescription(),
			'owner' => $this->event->getOwner(),
			'created' => $this->event->getCreated(),
			'access' => $this->event->getAccess(),
			'expire' => $this->event->getExpire(),
			'expiration' => $this->event->getExpiration(),
			'isAnonymous' => boolval($this->event->getIsAnonymous()),
			'fullAnonymous' => boolval($this->event->getFullAnonymous()),
			'allowMaybe' => boolval($this->event->getAllowMaybe()),
			'voteLimit' => $this->event->getVoteLimit(),
			'showResults' => $this->event->getShowResults(),
			'deleted' => boolval($this->event->getDeleted()),
			'deleteDate' => $this->event->getDeleteDate()
		],
		Http::STATUS_OK);

 	}

	/**
	 * getByToken
	 * Read all options of a poll based on a share token and return list as array
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function getByToken($token) {

		try {
			$this->acl->setToken($token);
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
		return $this->get($this->acl->getPollId());

	}

	/**
	 * Write poll (create/update)
	 * @NoAdminRequired
	 * @param Array $event
	 * @return DataResponse
	 */

	public function write($event) {

		try {
			// Find existing poll
			$this->event = $this->mapper->find($event['id']);
			$this->acl->setPollId($this->event->getId());

			if (!$this->acl->getAllowEdit()) {
				$this->logger->alert('Unauthorized write attempt from user ' . $this->userId);
				return new DataResponse(['message' => 'Unauthorized write attempt.'], Http::STATUS_UNAUTHORIZED);
			}

			$logMessageId = 'updatePoll';

			if (boolval($this->event->getDeleted()) !== boolval($event['deleted'])) {
				if ($event['deleted']) {
					$logMessageId = 'deletePoll';
					$this->event->setDeleteDate(date('Y-m-d'));
				} else {
					$logMessageId = 'restorePoll';
					$this->event->setDeleteDate('0');
				}
				$this->event->setDeleted($event['deleted']);
			}
			$this->event->setDeleted($event['deleted']);
		} catch (Exception $e) {
			$this->event = new Event();
			$this->acl->setPollId(0);

			if ($event['type'] === 'datePoll') {
				$this->event->setType(0);
			} elseif ($event['type'] === 'textPoll') {
				$this->event->setType(1);
			} else {
				$this->event->setType($event['type']);
			}

			$this->event->setOwner($this->userId);
			$this->event->setCreated(date('Y-m-d H:i:s',time()));
		} finally {

			$this->event->setTitle($event['title']);
			$this->event->setDescription($event['description']);

			$this->event->setAccess($event['access']);
			$this->event->setExpiration($event['expiration']);
			$this->event->setExpire(date('Y-m-d H:i:s', strtotime($event['expire'])));
			$this->event->setIsAnonymous(intval($event['isAnonymous']));
			$this->event->setFullAnonymous(intval($event['fullAnonymous']));
			$this->event->setAllowMaybe(intval($event['allowMaybe']));
			$this->event->setVoteLimit(intval($event['voteLimit']));
			$this->event->setShowResults($event['showResults']);

			if ($this->acl->getPollId() > 0) {
				$this->mapper->update($this->event);
				$this->logService->setLog($this->event->getId(), $logMessageId);
			} else {
				$this->mapper->insert($this->event);
				$this->logService->setLog($this->event->getId(), 'addPoll');
			}
			$this->event = $this->get($this->event->getId());
			return new DataResponse($this->event, Http::STATUS_OK);
		}
	}
}
