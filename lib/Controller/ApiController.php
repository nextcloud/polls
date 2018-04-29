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
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
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
		$userId,
		EventMapper $eventMapper,
		OptionsMapper $optionsMapper,
		VotesMapper $VotesMapper,
		CommentMapper $CommentMapper
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
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
	 * @return JSONResponse
	 */
	public function getPoll($hash) {
		try {
			$poll = $this->eventMapper->findByHash($hash);
		} catch (DoesNotExistException $e) {
			$data[] = [
				'Error' => 'Poll not found',
				'hash' => $hash
			];
			return new JSONResponse($data);
		};

		$options = $this->optionsMapper->findByPoll($poll->getId());
		$votes = $this->votesMapper->findByPoll($poll->getId());
		$comments = $this->commentMapper->findByPoll($poll->getId());
		foreach ($options as $optionElement) {
			$optionList[] = [
				'id' => $optionElement->getId(),
				'text' => $optionElement->getPollOptionText()
			];
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

		$data['poll'] = [
			'result' => 'found',
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
			// 'optionlist' => $optionList,
			'options' => [
				'pollDates' => [],
				'pollTexts' => $optionList
			]
		];			
		return new JSONResponse($data);
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $poll
	 * @return JSONResponse
	 */
	public function addPoll($event, $options, $mode) {
		
		$newEvent = new Event();

		If ($mode === 'edit') {
			// Existing poll shall be edited
			$oldPoll = $this->eventMapper->findByHash($event['hash']);

			// Check if current user is allowed to edit existing poll
			if ($oldPoll->getOwner() !== $this->userId) {
				// If current user is not owner of existing poll deny access
				return new TemplateResponse('polls', 'no.acc.tmpl', []);
			} else {
				// else take owner and hash of existing poll
				$newEvent->setOwner($oldPoll->getOwner());
				$newEvent->setHash($oldPoll->getHash());
			}		
		} else if ($mode === 'create') {
			// A new poll shall be created
			// Define current user as owner
			$newEvent->setOwner($this->userId);
			// create a new hash
			$newEvent->setHash(\OC::$server->getSecureRandom()->generate(
				16,
				ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
			));
		}
		// Set the entered configuration
		$newEvent->setTitle($event['title']);
		$newEvent->setDescription($event['description']);

		$newEvent->setCreated(date('Y-m-d H:i:s'));
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
 		$ins = $this->eventMapper->insert($newEvent);
		return new JSONResponse(json_encode($event));
	}
}
