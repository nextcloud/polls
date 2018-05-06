<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Security\ISecureRandom;

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Options;
use OCA\Polls\Db\OptionsMapper;
use OCA\Polls\Db\Votes;
use OCA\Polls\Db\VotesMapper;
use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;



class ApiController extends Controller {

	private $eventMapper;
	private $optionsMapper;
	private $votesMapper;
	private $commentMapper;

	/**
	 * PageController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param string $userId
	 * @param EventMapper $eventMapper
	 * @param OptionsMapper $optionsMapper
	 * @param VotesMapper $VotesMapper
	 * @param CommentMapper $CommentMapper
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IUserManager $userManager,
		$userId,
		EventMapper $eventMapper,
		OptionsMapper $optionsMapper,
		VotesMapper $VotesMapper,
		CommentMapper $CommentMapper
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->userManager = $userManager;
		$this->eventMapper = $eventMapper;
		$this->optionsMapper = $optionsMapper;
		$this->votesMapper = $VotesMapper;
		$this->commentMapper = $CommentMapper;
	}

  	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param string $hash
	 * @return DataResponse
	*/
	public function getPoll($hash) {

		if (!\OC::$server->getUserSession()->getUser() instanceof IUser) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}
		
		try {
			$poll = $this->eventMapper->findByHash($hash);
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		};

		try {
			$options = $this->optionsMapper->findByPoll($poll->getId());
		} catch (DoesNotExistException $e) {
			$optionList = '';
		};
		foreach ($options as $optionElement) {
			$optionList[] = [
				'id' => $optionElement->getId(),
				'text' => $optionElement->getPollOptionText(),
				'timestamp' => $optionElement->getTimestamp()
			];
		};

		try {
			$votes = $this->votesMapper->findByPoll($poll->getId());
		} catch (DoesNotExistException $e) {
			$voteslist = '';
		};
		foreach ($votes as $voteElement) {
			$votesList[] = [
				'id' => $voteElement->getId(),
				'userId' => $voteElement->getUserId(),
				'voteOptionId' => $voteElement->getVoteOptionId(),
				'voteOptionText' => $voteElement->getVoteOptionText(),
				'voteAnswer' => $voteElement->getVoteAnswer()
			];
		};

		try {
			$comments = $this->commentMapper->findByPoll($poll->getId());
		} catch (DoesNotExistException $e) {
			$commentsList = '';
		};
		foreach ($comments as $commentElement) {
			$commentsList[] = [
				'id' => $commentElement->getId(),
				'userId' => $commentElement->getUserId(),
				'date' => $commentElement->getDt() . ' UTC',
				'comment' => $commentElement->getComment()
			];
		};

		if ($poll->getType() == 0) {
			$pollType = 'datePoll'; 
		} else {
			$pollType = 'textPoll';
		};

		if ($poll->getExpire() == null) {
			$expiration = false;
			$expire = null;
		} else {
			$expiration = true;
			$expire = $poll->getExpire();
		}
		if ($poll->getOwner() !== \OC::$server->getUserSession()->getUser()->getUID()) {
			$mode = 'create';
		} else {
			$mode = 'edit';
		}
		
		$data['poll'] = [
			'result' => 'found',
			'mode' => $mode,
			'comments' => $commentsList,
			'votes' => $votesList,
			'event' => [
				'hash' => $hash,
				'id' => $poll->getId(),
				'type' => $pollType,
				'title' => $poll->getTitle(),
				'description' => $poll->getDescription(),
				'owner' => $poll->getOwner(),
				'created' => $poll->getCreated(),
				'access' => $poll->getAccess(),
				'expiration' => $expiration,
				'expire' => $poll->getExpire(),
				'is_anonymous' => $poll->getIsAnonymous(),
				'full_anonymous' => $poll->getFullAnonymous(),
				'disallowMaybe' => $poll->getDisallowMaybe()
			],
			'options' => [
				'pollDates' => [],
				'pollTexts' => $optionList
			]
		];

		return new DataResponse($data, Http::STATUS_OK);
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $poll
	 * @return DataResponse
	 */
	public function writePoll($event, $options, $mode) {
		$user = \OC::$server->getUserSession()->getUser();

		if (!$user instanceof IUser) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}
		$userId = \OC::$server->getUserSession()->getUser()->getUID();

		$newEvent = new Event();

		if ($mode === 'edit') {
			// Existing poll shall be edited
			$oldPoll = $this->eventMapper->findByHash($event['hash']);

			// Check if current user is allowed to edit existing poll
			if ($oldPoll->getOwner() !== $this->userId) {
				// If current user is not owner of existing poll deny access
				return new TemplateResponse('polls', 'no.acc.tmpl', []);
			} 

			// else take owner, hash and id of existing poll
			$newEvent->setOwner($oldPoll->getOwner());
			$newEvent->setHash($oldPoll->getHash());
			$newEvent->setId($oldPoll->getId());
					
		} else if ($mode === 'create') {
			// A new poll shall be created
			// Define current user as owner, set new creation date and create a new hash
			$newEvent->setOwner($this->userId);
			$newEvent->setCreated(date('Y-m-d H:i:s'));
			$newEvent->setHash(\OC::$server->getSecureRandom()->generate(
				16,
				ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
			));
		}
		// Set the configuration options entered by the user
		$newEvent->setTitle($event['title']);
		$newEvent->setDescription($event['description']);

		$newEvent->setType($event['type']);
		$newEvent->setAccess($event['access']);
		$newEvent->setIsAnonymous($event['is_anonymous']);
		$newEvent->setFullAnonymous($event['full_anonymous']);
		$newEvent->setDisallowMaybe($event['disallowMaybe']);
 		
		if ($event['expiration']) {
			$newEvent->setExpire($event['expire']);
		} else {
			$newEvent->setExpire(null);
		}
		
		if ($event['type'] === "datePoll") {
			$newEvent->setType(0);
		} else if ($event['type'] === "textPoll") {
			$newEvent->setType(1);
		}

		if ($mode === 'edit') {
			$this->eventMapper->update($newEvent);
			$this->optionsMapper->deleteByPoll($newEvent->getId());
			
		} else if ($mode === 'create') {
			$newEvent = $this->eventMapper->insert($newEvent);
		}

		if ($event['type'] === 'datePoll') {
			foreach ($options['pollDates'] as $optionElement) {
				$newOption = new Options();
				
				$newOption->setPollId($newEvent->getId());
				$newOption->setPollOptionText(date('Y-m-d H:i:s', $optionElement['timestamp']));
				$newOption->setTimestamp($optionElement['timestamp']);
				
				$this->optionsMapper->insert($newOption);
			}
		} else if ($event['type'] === "textPoll") {
			foreach ($options['pollTexts'] as $optionElement) {
				$newOption = new Options();
				
				$newOption->setPollId($newEvent->getId());
				$newOption->setpollOptionText($optionElement['text']);
				
				$this->optionsMapper->insert($newOption);
			}
		}
		return new DataResponse(array(
			'id' => $newEvent->getId(),
			'hash' => $newEvent->getHash()
		), Http::STATUS_OK);

	}
}
