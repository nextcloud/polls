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
use OCP\IConfig;
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
	private $systemConfig;

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
		IConfig $systemConfig,
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
		$this->systemConfig = $systemConfig;
		$this->userManager = $userManager;
		$this->eventMapper = $eventMapper;
		$this->optionsMapper = $optionsMapper;
		$this->votesMapper = $VotesMapper;
		$this->commentMapper = $CommentMapper;
	}

	/**
	* Transforms an array of users fromt he event's access list to an array
	* of nextcloud users
	* @NoAdminRequired
	* @NoCSRFRequired
	* @return Array
	*/
	private function convertAccessList($item) {
		$split = Array();
		if (strpos($item, 'user_') === 0) {
			$user = $this->userManager->get(substr($item, 5));
			$split = [
				'id' => $user->getUID(),
				'user' => $user->getUID(),
				'type' => 'user',
				'desc' => 'user',
				'icon' => 'icon-user',
				'displayName' => $user->getDisplayName(),
				'avatarURL' => '',
				'lastLogin' => $user->getLastLogin(),
				'cloudId' => $user->getCloudId()
			];
		} elseif (strpos($item, 'group_') === 0) {
			$group = substr($item, 6);
			$group = $this->groupManager->get($group);
			$split = [
				'id' => $group->getGID(),
				'user' => $group->getGID(),
				'type' => 'group',
				'desc' => 'group',
				'icon' => 'icon-group',
				'displayName' => $group->getDisplayName(),
				'avatarURL' => '',
			];
		}


		return($split);
	}

	/**
	* Transforms an event into an array that fits to the expected structure
	* of the vue app
	* @NoAdminRequired
	* @NoCSRFRequired
	* @PublicPage
	* @param object $event
	* @return Array
	*/
	private function convertEvent($event) {

		if ($event->getType() == 0) {
			$pollType = 'datePoll';
		} else {
			$pollType = 'textPoll';
		};

		$accessType = $event->getAccess();
		if (!strpos('|public|hidden|registered', $accessType)) {
			$accessType = 'select';
		}

		if ($event->getExpire() === null) {
			$expired = false;
			$expiration = false;
		} else {
			$expired = time() > strtotime($event->getExpire());
			$expiration = true;
		}

		return [
			'id' => $event->getId(),
			'hash' => $event->getHash(),
			'type' => $pollType,
			'title' => $event->getTitle(),
			'description' => $event->getDescription(),
			'owner' => $event->getOwner(),
			'created' => $event->getCreated(),
			'access' => $accessType,
			'expiration' => $expiration,
			'expired' => $expired,
			'expirationDate' => $event->getExpire(),
			'isAnonymous' => $event->getIsAnonymous(),
			'fullAnonymous' => $event->getFullAnonymous(),
			'disallowMaybe' => $event->getDisallowMaybe()
		];
	}

	/**
	* Check If current user is in the access list
	* @param string $accessList
	* @return Boolean
	*/
	private function checkUserAccess($accessList) {
		foreach ($accessList as $accessItem ) {
			if ($accessItem['type'] === 'user' &&  $accessItem['id'] === \OC::$server->getUserSession()->getUser()->getUID()) {
				return true;
			}
		}
		return false;
	}

	/**
	* Check If current user is in the access list
	* @param string $accessList
	* @return Boolean
	*/
	private function checkGroupAccess($accessList) {
		foreach ($accessList as $accessItem ) {
			if ($accessItem['type'] === 'group' &&  $this->groupManager->isInGroup(\OC::$server->getUserSession()->getUser()->getUID(),$accessItem['id'])) {
				return true;
			}
		}
		return false;
	}

	/**
	* Read an entire poll based on it's id
	* @NoAdminRequired
	* @NoCSRFRequired
	* @param string $pollId
	* @return Array
	*/
	public function getOptions($pollId) {
		$optionsList = Array();
		try {
			$options = $this->optionsMapper->findByPoll($pollId);
			foreach ($options as $optionElement) {
				$optionList[] = [
					'id' => $optionElement->getId(),
					'text' => htmlspecialchars_decode($optionElement->getPollOptionText()),
					'timestamp' => $optionElement->getTimestamp()
				];
			};
		} catch (DoesNotExistException $e) {
			return [];
		};
		return $optionList;
	}

	/**
	* Read an entire poll based on it's id
	* @NoAdminRequired
	* @NoCSRFRequired
	* @param string $pollId
	* @return Array
	*/
	public function getVotes($pollId) {
		$votesList = Array();
		try {
			$votes = $this->votesMapper->findByPoll($pollId);
			foreach ($votes as $voteElement) {
				$votesList[] = [
					'id' => $voteElement->getId(),
					'userId' => $voteElement->getUserId(),
					'voteOptionId' => $voteElement->getVoteOptionId(),
					'voteOptionText' => htmlspecialchars_decode($voteElement->getVoteOptionText()),
					'voteAnswer' => $voteElement->getVoteAnswer()
				];
			};
		} catch (DoesNotExistException $e) {
			return [];
		};
		return $votesList;
	}

	/**
	* Read an entire poll based on it's id
	* @NoAdminRequired
	* @NoCSRFRequired
	* @param string $pollId
	* @return Array
	*/
	public function getComments($pollId) {
		$commentsList = Array();
		try {
			$comments = $this->commentMapper->findByPoll($pollId);
			foreach ($comments as $commentElement) {
				$commentsList[] = [
					'id' => $commentElement->getId(),
					'userId' => $commentElement->getUserId(),
					'date' => $commentElement->getDt() . ' UTC',
					'comment' => $commentElement->getComment()
				];
			};
		} catch (DoesNotExistException $e) {
			return [];
		};
		return $commentsList;
	}

	/**
	* Read an entire poll based on it's id
	* @NoAdminRequired
	* @NoCSRFRequired
	* @param string $pollId
	* @return Array
	*/
	public function getEvent($pollId) {

		if (!\OC::$server->getUserSession()->getUser() instanceof IUser) {
			$currentUser = '';
		} else {
			$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		}

		try {
			$event = $this->eventMapper->find($pollId);
		} catch (DoesNotExistException $e) {
			return [];
		};

		if ($event->getType() == 0) {
			$pollType = 'datePoll';
		} else {
			$pollType = 'textPoll';
		};

		$accessType = $event->getAccess();
		if (!strpos('|public|hidden|registered', $accessType)) {
			$accessType = 'select';
		}

		if ($event->getExpire() === null) {
			$expired = false;
			$expiration = false;
		} else {
			$expired = time() > strtotime($event->getExpire());
			$expiration = true;
		}

		return [
			'id' => $event->getId(),
			'hash' => $event->getHash(),
			'type' => $pollType,
			'title' => $event->getTitle(),
			'description' => $event->getDescription(),
			'owner' => $event->getOwner(),
			'created' => $event->getCreated(),
			'access' => $accessType,
			'expiration' => $expiration,
			'expired' => $expired,
			'expirationDate' => $event->getExpire(),
			'isAnonymous' => $event->getIsAnonymous(),
			'fullAnonymous' => $event->getFullAnonymous()
		];
	}

	/**
	* Read an entire poll based on it's id
	* @NoAdminRequired
	* @NoCSRFRequired
	* @param string $pollId
	* @return Array
	*/
	public function getShares($pollId) {

		if (!\OC::$server->getUserSession()->getUser() instanceof IUser) {
			$currentUser = '';
		} else {
			$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		}

		try {
			$poll = $this->eventMapper->find($pollId);
		} catch (DoesNotExistException $e) {
			return [];
		};

		if (!strpos('|public|hidden|registered', $poll->getAccess())) {
			$accessList = explode(';',$poll->getAccess());
			$accessList = array_filter($accessList);
			$accessList = array_map(Array($this,'convertAccessList'), $accessList);
		} else {
			return [];
		}
		return $accessList;
	}

	/**
	* @param string $event
	* @return Boolean
	*/
	private function grantAccessAs($pollId) {
		if (!\OC::$server->getUserSession()->getUser() instanceof IUser) {
			$currentUser = '';
		} else {
			$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		}
		$event = $this->getEvent($pollId);
		$accessList =$this->getShares($pollId);

		if ($event['owner'] === $currentUser) {
			return 'owner';
		} elseif ($event['access'] === 'public') {
			return 'public';
		} elseif ($event['access'] === 'registered' && \OC::$server->getUserSession()->getUser() instanceof IUser){
			return 'registered';
		} elseif ($this->checkUserAccess($accessList)) {
			return 'userInvitation';
		} elseif ($this->checkGroupAccess($accessList)) {
			return 'groupInvitation';
		} elseif ($this->groupManager->isAdmin($currentUser)) {
			return 'admin';
		} else {
			return 'none';
		}
	}


	/**
	* Read an entire poll based on it's id
	* @NoAdminRequired
	* @NoCSRFRequired
	* @param string $id
	* @return Array
	*/
	public function getPoll($pollId) {

		if (!\OC::$server->getUserSession()->getUser() instanceof IUser) {
			$currentUser = '';
		} else {
			$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		}
		$data['poll'] = ['result' => 'notFound'];
		$result = 'foundById';
		try {
			// try to find poll by hash
			$pollId = $this->eventMapper->findByHash($pollId)->id;
			$result = 'foundByHash';
		} catch (DoesNotExistException $e) {
			// hash is not found, try id in finally
		} finally {
			try {
				$poll = $this->eventMapper->find($pollId);
			} catch (DoesNotExistException $e) {
				return $data;
			}
		}


		$event = $this->getEvent($pollId);

		if ($event['owner'] !== $currentUser && !$this->groupManager->isAdmin($currentUser)) {
			$mode = 'create';
		} else {
			$mode = 'edit';
		}
		;

		$data['poll'] = [
			'result' => $result,
			'grantedAs' => $this->grantAccessAs($pollId),
			'mode' => $mode,
			'comments' => $this->getComments($pollId),
			'votes' => $this->getVotes($pollId),
			'shares' => $this->getShares($pollId),
			'event' => $event,
			'options' => [
				'pollDates' => [],
				'pollTexts' => $this->getOptions($pollId)
			]
		];
		return $data;
	}

  	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	* @return DataResponse
	*/
	public function getSiteUsersAndGroups($query = '', $getGroups = true, $getUsers = true, $skipGroups = array(), $skipUsers = array()) {
		$list = array();
		$data = array();
		if ($getGroups) {
			$groups = $this->groupManager->search($query);
			foreach ($groups as $group) {
				if (!in_array($group->getGID(), $skipGroups)) {
					$list[] = [
						'id' => $group->getGID(),
						'user' => $group->getGID(),
						'type' => 'group',
						'desc' => 'group',
						'icon' => 'icon-group',
						'displayName' => $group->getGID(),
						'avatarURL' => ''
					];
				}
			}
		}
		if ($getUsers) {
			$users = $this->userManager->searchDisplayName($query);
			foreach ($users as $user) {
				if (!in_array($user->getUID(), $skipUsers)) {
					$list[] = [
						'id' => $user->getUID(),
						'user' => $user->getUID(),
						'type' => 'user',
						'desc' => 'user',
						'icon' => 'icon-user',
						'displayName' => $user->getDisplayName(),
						'avatarURL' => '',
						'lastLogin' => $user->getLastLogin(),
						'cloudId' => $user->getCloudId()
					];
				}
			}
		}

		$data['siteusers'] = $list;
		return new DataResponse($data, Http::STATUS_OK);
	}


	/**
	* Read an entire poll based on it's hash
	* @NoAdminRequired
	* @NoCSRFRequired
	* @PublicPage
	* @param string $hash
	* @return DataResponse
	*/

	public function getPolls() {
		if (!\OC::$server->getUserSession()->getUser() instanceof IUser) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		}

		try {
			$events = $this->eventMapper->findAll();
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
		$eventsList = Array();
		foreach ($events as $eventElement) {
			$eventsList[] = $this->getEvent($eventElement->id);
		};

		return new DataResponse($eventsList, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $poll
	 * @return DataResponse
	 */
	public function writePoll($event, $options, $shares, $mode) {
		if (!\OC::$server->getUserSession()->getUser() instanceof IUser) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		} else {
			$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
			$AdminAccess = $this->groupManager->isAdmin($currentUser);
		}

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
			$newEvent->setExpire($event['expirationDate']);
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
			if ($oldPoll->getOwner() !== $currentUser && !$AdminAccess) {
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
			$newEvent->setOwner($currentUser);
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
				$newOption->setpollOptionText(trim(htmlspecialchars($optionElement['text'])));

				$this->optionsMapper->insert($newOption);
			}
		}
		return new DataResponse(array(
			'id' => $newEvent->getId(),
			'hash' => $newEvent->getHash()
		), Http::STATUS_OK);

	}

	private function getVendor() {
		// this should really be a JSON file
		require \OC::$SERVERROOT . '/version.php';
		/** @var string $vendor */
		return (string) $vendor;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return DataResponse
	 */
	public function getSystem() {
		$userId = \OC::$server->getUserSession()->getUser()->getUID();
		$data['system'] = [
			'versionArray' => \OCP\Util::getVersion(),
			'version' => implode('.', \OCP\Util::getVersion()),
			'vendor' => $this->getVendor(),
			'language' => $this->systemConfig->getUserValue($userId, 'core', 'lang')
		];
		return new DataResponse($data, Http::STATUS_OK);
	}
}
