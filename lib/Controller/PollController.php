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

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Service\LogService;
use OCA\Polls\Service\MailService;
use OCA\Polls\Model\Acl;

class PollController extends Controller {

	private $userId;
	private $mapper;
	private $logger;
	private $groupManager;
	private $userManager;
	private $poll;
	private $logService;
	private $mailService;
	private $acl;

	/**
	 * CommentController constructor.
	 * @param string $appName
	 * @param $userId
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param PollMapper $mapper
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param LogService $logService
	 * @param MailService $mailService
	 * @param Acl $acl
	 */

	public function __construct(
		string $appName,
		$userId,
		IRequest $request,
		ILogger $logger,
		PollMapper $mapper,
		Poll $poll,
		IGroupManager $groupManager,
		IUserManager $userManager,
		LogService $logService,
		MailService $mailService,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->mapper = $mapper;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->poll = $poll;
		$this->logService = $logService;
		$this->mailService = $mailService;
		$this->acl = $acl;
	}

	/**
	 * list
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @return DataResponse
	 */

	public function list() {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			try {
				$polls = array_values(array_filter($this->mapper->findAll(), function($item) {
					return $this->acl->setPollId($item->getId())->getAllowView();
				}));
				return new DataResponse($polls, Http::STATUS_OK);
			} catch (DoesNotExistException $e) {
				return new DataResponse($e, Http::STATUS_NOT_FOUND);
			}
		}
	}

	/**
	 * get
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

			$this->poll = $this->mapper->find($pollId);

			return new DataResponse($this->poll, Http::STATUS_OK);

		} catch (DoesNotExistException $e) {
			$this->logger->info('Poll ' . $pollId . ' not found!', ['app' => 'polls']);
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
 		}
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
			return new DataResponse($this->get($this->acl->getPollId()), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

	}

	/**
	 * write
	 * @NoAdminRequired
	 * @param Array $poll
	 * @return DataResponse
	 */

	public function write($poll) {

		try {
			// Find existing poll
			$this->poll = $this->mapper->find($poll['id']);
			$this->acl->setPollId($this->poll->getId());
			if (!$this->acl->getAllowEdit()) {
				$this->logger->alert('Unauthorized write attempt from user ' . $this->userId);
				return new DataResponse(['message' => 'Unauthorized write attempt.'], Http::STATUS_UNAUTHORIZED);
			}

			$logMessageId = 'updatePoll';

		} catch (Exception $e) {
			$logMessageId = 'addPoll';
			$this->poll = new Poll();

			$this->poll->setType($poll['type']);
			$this->poll->setOwner($this->userId);
			$this->poll->setCreated(time());
		} finally {
			$this->poll->setTitle($poll['title']);
			$this->poll->setDescription($poll['description']);
			$this->poll->setAccess($poll['access']);
			$this->poll->setExpire($poll['expire']);
			$this->poll->setAnonymous(intval($poll['anonymous']));
			$this->poll->setFullAnonymous(intval($poll['fullAnonymous']));
			$this->poll->setAllowMaybe(intval($poll['allowMaybe']));
			$this->poll->setVoteLimit(intval($poll['voteLimit']));
			$this->poll->setSettings('');
			$this->poll->setOptions('');
			$this->poll->setShowResults($poll['showResults']);
			$this->poll->setDeleted($poll['deleted']);
			$this->poll->setAdminAccess($poll['adminAccess']);

			if ($this->poll->getId() > 0) {
				$this->mapper->update($this->poll);
				$this->logService->setLog($this->poll->getId(), $logMessageId);
			} else {
				$this->mapper->insert($this->poll);
				$this->logService->setLog($this->poll->getId(), 'addPoll');
			}
			return new DataResponse($this->poll, Http::STATUS_OK);
		}
	}
}
