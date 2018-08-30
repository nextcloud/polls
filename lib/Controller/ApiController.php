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

use OCP\IGroupManager;
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
		IGroupManager $groupManager,
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
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->eventMapper = $eventMapper;
		$this->optionsMapper = $optionsMapper;
		$this->votesMapper = $VotesMapper;
		$this->commentMapper = $CommentMapper;
	}

  	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	* @return DataResponse
	*/
	public function getSiteUsersAndGroups($getGroups = true, $getUsers = true, $skipGroups = array(), $skipUsers = array()) {
		$list = array();
		$data = array();
		if ($getGroups) {
			$groups = $this->groupManager->search('');
			foreach ($groups as $group) {
				if (!in_array($group->getGID(), $skipGroups)) {
					$list[] = [
						'id' => $group->getGID(),
						'type' => 'group',
						'displayName' => $group->getGID(),
						'avatarURL' => ''
					];
				}
			}
		}
		if ($getUsers) {
			$users = $this->userManager->searchDisplayName('');
			foreach ($users as $user) {
				if (!in_array($user->getUID(), $skipUsers)) {
					$list[] = [
						'id' => $user->getUID(),
						'type' => 'user',
						'displayName' => $user->getDisplayName(),
						'avatarURL' => ''
					];
				}
			}
		}

		$data['siteusers'] = $list;
		return new DataResponse($data, Http::STATUS_OK);
	}
  	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	* @return Array
	*/	
	function convertAccessList($item) {
		$split = Array();
		if (strpos($item, 'user_') === 0) {
			$user = $this->userManager->get(substr($item, 5));
			$split = [
				'id' => $user->getUID(),
				'type' => 'user',
				'displayName' => $user->getDisplayName(),
				'avatarURL' => ''
			];
		} elseif (strpos($item, 'group_') === 0) {
			$group = substr($item, 6);
			$group = $this->groupManager->get($group);
			$split = [
				'id' => $group->getGID(),
				'type' => 'group',
				'displayName' => $group->getDisplayName(),
				'avatarURL' => '',
			];
		}
		

		return($split);
	}

  	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	* @return DataResponse
	*/
	public function getGrantedEvents() {
		if (!\OC::$server->getUserSession()->getUser() instanceof IUser) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}
		$events = $this->eventMapper->findAllForUserWithInfo(\OC::$server->getUserSession()->getUser()->getUID());
		$data = array();
		
		foreach ($events as $event) {
				$data[] = [
				'id' => $event->getId(),
				'hash' => $event->getHash(),
				'type' => $event->getType(),
				'title' => $event->getTitle(),
				'description' => $event->getDescription(),
				'owner' => $event->getOwner(),
				'created' => $event->getCreated(),
				'access' => $event->getAccess(),
				'expire' => $event->getExpire(),
				'is_anonymous' => $event->getIsAnonymous(),
				'full_anonymous' => $event->getFullAnonymous(),
				'disallow_maybe' => $event->getDisallowMaybe()
			];
		}

		return new DataResponse($data, Http::STATUS_OK);
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

			$expiration = ($poll->getExpire() !== null);

			if ($poll->getExpire() === null) {
				$expired = false;
			} else {
				$expired = time() > strtotime($poll->getExpire());
			}

			if ($poll->getType() == 0) {
				$pollType = 'datePoll'; 
			} else {
				$pollType = 'textPoll';
			};

			if ($poll->getOwner() !== \OC::$server->getUserSession()->getUser()->getUID()) {
				$mode = 'create';
			} else {
				$mode = 'edit';
			}
			$accessList = Array();
			$accessType = $poll->getAccess();
			if (!strpos('|public|hidden|registered', $accessType)) {
				$accessList = explode(';',$accessType);
				$accessList = array_filter($accessList);
				$accessList = array_map(Array($this,'convertAccessList'), $accessList);
				$accessType = 'select';
			}
				
			$data = array();
			$commentsList = array();
			$optionList = array();
			$votesList = array();
			
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		};


		try {
			$options = $this->optionsMapper->findByPoll($poll->getId());
			foreach ($options as $optionElement) {
				$optionList[] = [
					'id' => $optionElement->getId(),
					'text' => $optionElement->getPollOptionText(),
					'timestamp' => $optionElement->getTimestamp()
				];
			};
		} catch (DoesNotExistException $e) {
			// ignore
		};

		try {
			$votes = $this->votesMapper->findByPoll($poll->getId());
			foreach ($votes as $voteElement) {
				$votesList[] = [
					'id' => $voteElement->getId(),
					'userId' => $voteElement->getUserId(),
					'voteOptionId' => $voteElement->getVoteOptionId(),
					'voteOptionText' => $voteElement->getVoteOptionText(),
					'voteAnswer' => $voteElement->getVoteAnswer()
				];
			};
		} catch (DoesNotExistException $e) {
			// ignore
		};

		try {
			$comments = $this->commentMapper->findByPoll($poll->getId());
			foreach ($comments as $commentElement) {
				$commentsList[] = [
					'id' => $commentElement->getId(),
					'userId' => $commentElement->getUserId(),
					'date' => $commentElement->getDt() . ' UTC',
					'comment' => $commentElement->getComment()
				];
			};
		} catch (DoesNotExistException $e) {
			// ignore
		};
	
		$data['poll'] = [
			'result' => 'found',
			'mode' => $mode,
			'comments' => $commentsList,
			'votes' => $votesList,
			'shares' => $accessList,
			'event' => [
				'id' => $poll->getId(),
				'hash' => $hash,
				'type' => $pollType,
				'title' => $poll->getTitle(),
				'description' => $poll->getDescription(),
				'owner' => $poll->getOwner(),
				'created' => $poll->getCreated(),
				'access' => $accessType,
				'expiration' => $expiration,
				'expired' => $expired,
				'expire' => $poll->getExpire(),
				'isAnonymous' => $poll->getIsAnonymous(),
				'fullAnonymous' => $poll->getFullAnonymous(),
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
	public function writePoll($event, $options, $shares, $mode) {
		$user = \OC::$server->getUserSession()->getUser();

		if (!$user instanceof IUser) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}
		$userId = \OC::$server->getUserSession()->getUser()->getUID();

		$newEvent = new Event();

		// Set the configuration options entered by the user
		$newEvent->setTitle($event['title']);
		$newEvent->setDescription($event['description']);

		$newEvent->setType($event['type']);
		$newEvent->setIsAnonymous($event['isAnonymous']);
		$newEvent->setFullAnonymous($event['fullAnonymous']);
		$newEvent->setDisallowMaybe($event['disallowMaybe']);
	
		if ($event['access'] === 'select') {
			$shareAccess = '';
			foreach ($shares as $shareElement) {
				if ($shareElement['type'] === 'user') {
					$shareAccess = $shareAccess . 'user_' . $shareElement['id'] . ';';
				} elseif ($shareElement['type'] === 'group') {
					$shareAccess = $shareAccess . 'group_' . $shareElement['id'] . ';';
				}
			}
			$newEvent->setAccess(rtrim($shareAccess, ';'));
		} else {
			$newEvent->setAccess($event['access']);
		}

		if ($event['expiration']) {
			$newEvent->setExpire($event['expire']);
		} else {
			$newEvent->setExpire(null);
		}
		
		if ($event['type'] === 'datePoll') {
			$newEvent->setType(0);
		} elseif ($event['type'] === 'textPoll') {
			$newEvent->setType(1);
		}

		if ($mode === 'edit') {
			// Edit existing poll
			$oldPoll = $this->eventMapper->findByHash($event['hash']);

			// Check if current user is allowed to edit existing poll
			if ($oldPoll->getOwner() !== $userId) {
				// If current user is not owner of existing poll deny access
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			} 

			// else take owner, hash and id of existing poll
			$newEvent->setOwner($oldPoll->getOwner());
			$newEvent->setHash($oldPoll->getHash());
			$newEvent->setId($oldPoll->getId());
			$this->eventMapper->update($newEvent);
			$this->optionsMapper->deleteByPoll($newEvent->getId());
					
		} elseif ($mode === 'create') {
			// Create new poll
			// Define current user as owner, set new creation date and create a new hash
			$newEvent->setOwner($userId);
			$newEvent->setCreated(date('Y-m-d H:i:s'));
			$newEvent->setHash(\OC::$server->getSecureRandom()->generate(
				16,
				ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
			));
			$newEvent = $this->eventMapper->insert($newEvent);
		}
		
		// Update options
		if ($event['type'] === 'datePoll') {
			foreach ($options['pollDates'] as $optionElement) {
				$newOption = new Options();
				
				$newOption->setPollId($newEvent->getId());
				$newOption->setPollOptionText(date('Y-m-d H:i:s', $optionElement['timestamp']));
				$newOption->setTimestamp($optionElement['timestamp']);
				
				$this->optionsMapper->insert($newOption);
			}
		} elseif ($event['type'] === "textPoll") {
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
