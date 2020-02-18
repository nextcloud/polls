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
use OCP\IL10N;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Service\LogService;
use OCA\Polls\Service\MailService;
use OCA\Polls\Model\Acl;

class PollController extends Controller {

	private $userId;
	private $pollMapper;
	private $optionMapper;
	private $trans;
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
	 * @param IL10N $trans
	 * @param PollMapper $pollMapper
	 * @param OptionMapper $optionMapper
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
		IL10N $trans,
		PollMapper $pollMapper,
		OptionMapper $optionMapper,
		Poll $poll,
		IGroupManager $groupManager,
		IUserManager $userManager,
		LogService $logService,
		MailService $mailService,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->trans = $trans;
		$this->pollMapper = $pollMapper;
		$this->optionMapper = $optionMapper;
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
	 * @return DataResponse
	 */

	public function list() {
		if (\OC::$server->getUserSession()->isLoggedIn()) {

			$pollList = [];

			try {

				$polls = $this->pollMapper->findAll();

				// TODO: Not the elegant way. Improvement neccessary
				foreach ($polls as $poll) {
					$combinedPoll = (object) array_merge(
        				(array) json_decode(json_encode($poll)), (array) json_decode(json_encode($this->acl->setPollId($poll->getId()))));
					if ($combinedPoll->allowView) {
						$pollList[] = $combinedPoll;
					}
				}

				return new DataResponse($pollList, Http::STATUS_OK);
			} catch (DoesNotExistException $e) {
				return new DataResponse($e, Http::STATUS_NOT_FOUND);
			}
		} else {
			return new DataResponse([], Http::STATUS_OK);
		}

	}

	/**
	 * get
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return array
	 */
 	public function get($pollId) {

 		try {
			if (!$this->acl->getFoundByToken()) {
				$this->acl->setPollId($pollId);
			}
			$this->poll = $this->pollMapper->find($pollId);
			if (!$this->acl->getAllowView()) {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}
			return new DataResponse([
				'poll' => $this->poll,
				'acl' => $this->acl
			], Http::STATUS_OK);

		} catch (DoesNotExistException $e) {
			$this->logger->info('Poll ' . $pollId . ' not found!', ['app' => 'polls']);
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
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
			return $this->get($this->acl->setToken($token)->getPollId());
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

	}

	/**
	 * delete
	 * @NoAdminRequired
	 * @param Array $poll
	 * @return DataResponse
	 */

	public function delete($pollId) {

		try {
			// Find existing poll
			$this->poll = $this->pollMapper->find($pollId);
			$this->acl->setPollId($this->poll->getId());

			if (!$this->acl->getAllowEdit()) {
				$this->logger->alert('Unauthorized delete attempt from user ' . $this->userId);
				return new DataResponse(['message' => 'Unauthorized write attempt.'], Http::STATUS_UNAUTHORIZED);
			}

			if ($this->poll->getDeleted()) {
				$this->poll->setDeleted(0);
			} else {
				$this->poll->setDeleted(time());
			}

			$this->pollMapper->update($this->poll);
			$this->logService->setLog($this->poll->getId(), 'deletePoll');
			return new DataResponse([
				'deleted' => $pollId
			], Http::STATUS_OK);

		} catch (Exception $e) {
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
			$this->poll = $this->pollMapper->find($poll['id']);
			$this->acl->setPollId($this->poll->getId());
			if (!$this->acl->getAllowEdit()) {
				$this->logger->alert('Unauthorized write attempt from user ' . $this->userId);
				return new DataResponse(['message' => 'Unauthorized write attempt.'], Http::STATUS_UNAUTHORIZED);
			}

		} catch (Exception $e) {
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
			$this->poll->setFullAnonymous(intval($poll['fullAnonymous']) * $this->poll->getAnonymous());
			$this->poll->setAllowMaybe(intval($poll['allowMaybe']));
			$this->poll->setVoteLimit(intval($poll['voteLimit']));
			$this->poll->setSettings('');
			$this->poll->setOptions('');
			$this->poll->setShowResults($poll['showResults']);
			$this->poll->setDeleted($poll['deleted']);
			$this->poll->setAdminAccess($poll['adminAccess']);

			if ($this->poll->getId() > 0) {
				$this->pollMapper->update($this->poll);
				$this->logService->setLog($this->poll->getId(), 'updatePoll');
			} else {
				$this->pollMapper->insert($this->poll);
				$this->logService->setLog($this->poll->getId(), 'addPoll');
			}
			$this->acl->setPollId($this->poll->getId());
			return new DataResponse([
				'poll' => $this->poll,
				'acl' => $this->acl
			], Http::STATUS_OK);
		}
	}

	/**
	 * clone
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function clone($pollId) {
		$this->poll = $this->pollMapper->find($pollId);

		$clonePoll = new Poll();
		$clonePoll->setOwner($this->userId);
		$clonePoll->setCreated(time());
		$clonePoll->setTitle('Clone of ' . $this->poll->getTitle());
		$clonePoll->setDeleted(0);

		$clonePoll->setType($this->poll->getType());
		$clonePoll->setDescription($this->poll->getDescription());
		$clonePoll->setAccess($this->poll->getAccess());
		$clonePoll->setExpire($this->poll->getExpire());
		$clonePoll->setAnonymous(intval($this->poll->getAnonymous()));
		$clonePoll->setFullAnonymous(intval($this->poll->getFullAnonymous())  * $clonePoll->getAnonymous());
		$clonePoll->setAllowMaybe(intval($this->poll->getAllowMaybe()));
		$clonePoll->setVoteLimit(intval($this->poll->getVoteLimit()));
		$clonePoll->setSettings('');
		$clonePoll->setOptions('');
		$clonePoll->setShowResults($this->poll->getShowResults());
		$clonePoll->setAdminAccess($this->poll->getAdminAccess());

		$this->pollMapper->insert($clonePoll);
		$this->logService->setLog($clonePoll->getId(), 'addPoll');

		foreach ($this->optionMapper->findByPoll($pollId) as $option) {
			$newOption = new Option();
			$newOption->setPollId($clonePoll->getId());
			$newOption->setPollOptionText($option->getPollOptionText());
			$newOption->setTimestamp($option->getTimestamp());

			$this->optionMapper->insert($newOption);
		}
		return new DataResponse([
			'pollId' => $clonePoll->getId()
		], Http::STATUS_OK);

	}

}
