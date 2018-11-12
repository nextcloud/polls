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

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Notification;
use OCA\Polls\Db\NotificationMapper;
use OCA\Polls\Db\Options;
use OCA\Polls\Db\OptionsMapper;
use OCA\Polls\Db\Votes;
use OCA\Polls\Db\VotesMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAvatarManager;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use OCP\User; //To do: replace according to API
use OCP\Util;

class PageController extends Controller {

	private $userId;
	private $commentMapper;
	private $eventMapper;
	private $notificationMapper;
	private $optionsMapper;
	private $votesMapper;
	private $urlGenerator;
	private $userMgr;
	private $avatarManager;
	private $logger;
	private $trans;
	private $groupManager;

	/**
	 * PageController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userMgr
	 * @param IGroupManager $groupManager
	 * @param IAvatarManager $avatarManager
	 * @param ILogger $logger
	 * @param IL10N $trans
	 * @param IURLGenerator $urlGenerator
	 * @param string $userId
	 * @param CommentMapper $commentMapper
	 * @param EventMapper $eventMapper
	 * @param NotificationMapper $notificationMapper
	 * @param OptionsMapper $optionsMapper
	 * @param VotesMapper $VotesMapper
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IUserManager $userMgr,
		IGroupManager $groupManager,
		IAvatarManager $avatarManager,
		ILogger $logger,
		IL10N $trans,
		IURLGenerator $urlGenerator,
		$userId,
		CommentMapper $commentMapper,
		OptionsMapper $optionsMapper,
		EventMapper $eventMapper,
		NotificationMapper $notificationMapper,
		VotesMapper $VotesMapper
	) {
		parent::__construct($appName, $request);
		$this->userMgr = $userMgr;
		$this->groupManager = $groupManager;
		$this->avatarManager = $avatarManager;
		$this->logger = $logger;
		$this->trans = $trans;
		$this->urlGenerator = $urlGenerator;
		$this->userId = $userId;
		$this->commentMapper = $commentMapper;
		$this->eventMapper = $eventMapper;
		$this->notificationMapper = $notificationMapper;
		$this->optionsMapper = $optionsMapper;
		$this->votesMapper = $VotesMapper;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		$polls = $this->eventMapper->findAllForUserWithInfo($this->userId);
		$comments = $this->commentMapper->findDistinctByUser($this->userId);
		$votes = $this->votesMapper->findDistinctByUser($this->userId);
		$response = new TemplateResponse('polls', 'main.tmpl', [
			'polls' => $polls,
			'comments' => $comments,
			'votes' => $votes,
			'userId' => $this->userId,
			'userMgr' => $this->userMgr,
			'urlGenerator' => $this->urlGenerator
		]);
		$csp = new ContentSecurityPolicy();
		$response->setContentSecurityPolicy($csp);
		return $response;
	}

	/**
	 * @param int $pollId
	 * @param string $from
	 */
	private function sendNotifications($pollId, $from) {
		$poll = $this->eventMapper->find($pollId);
		$notifications = $this->notificationMapper->findAllByPoll($pollId);
		foreach ($notifications as $notification) {
			if ($from === $notification->getUserId()) {
				continue;
			}
			$email = \OC::$server->getConfig()->getUserValue($notification->getUserId(), 'settings', 'email');
			if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				continue;
			}
			$url = $this->urlGenerator->getAbsoluteURL(
				$this->urlGenerator->linkToRoute('polls.page.goto_poll',
					array('hash' => $poll->getHash()))
			);

			$recUser = $this->userMgr->get($notification->getUserId());
			$sendUser = $this->userMgr->get($from);
			$rec = '';
			if ($recUser !== null) {
				$rec = $recUser->getDisplayName();
			}
			$sender = $from;
			if ($sendUser !== null) {
				$sender = $sendUser->getDisplayName();
			}
			$msg = $this->trans->t('Hello %s,<br/><br/><strong>%s</strong> participated in the poll \'%s\'.<br/><br/>To go directly to the poll, you can use this <a href="%s">link</a>',
				array(
					$rec,
					$sender,
					$poll->getTitle(),
					$url
				));

			$msg .= '<br/><br/>';

			$toName = $this->userMgr->get($notification->getUserId())->getDisplayName();
			$subject = $this->trans->t('Polls App - New Activity');
			$fromAddress = Util::getDefaultEmailAddress('no-reply');
			$fromName = $this->trans->t('Polls App') . ' (' . $from . ')';

			try {
				/** @var IMailer $mailer */
				$mailer = \OC::$server->getMailer();
				/** @var \OC\Mail\Message $message */
				$message = $mailer->createMessage();
				$message->setSubject($subject);
				$message->setFrom(array($fromAddress => $fromName));
				$message->setTo(array($email => $toName));
				$message->setHtmlBody($msg);
				$mailer->send($message);
			} catch (\Exception $e) {
				$message = 'Error sending mail to: ' . $toName . ' (' . $email . ')';
				Util::writeLog('polls', $message, Util::ERROR);
			}
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param string $hash
	 * @return TemplateResponse
	 */
	public function gotoPoll($hash) {
		try {
			$poll = $this->eventMapper->findByHash($hash);
		} catch (DoesNotExistException $e) {
			return new TemplateResponse('polls', 'no.acc.tmpl', []);
		}
		$options = $this->optionsMapper->findByPoll($poll->getId());
		$votes = $this->votesMapper->findByPoll($poll->getId());
		$participants = $this->votesMapper->findParticipantsByPoll($poll->getId());
		$comments = $this->commentMapper->findByPoll($poll->getId());

		try {
			$notification = $this->notificationMapper->findByUserAndPoll($poll->getId(), $this->userId);
		} catch (DoesNotExistException $e) {
			$notification = null;
		}
		if ($this->hasUserAccess($poll)) {
			return new TemplateResponse('polls', 'goto.tmpl', [
				'poll' => $poll,
				'options' => $options,
				'comments' => $comments,
				'votes' => $votes,
				'participant' => $participants,
				'notification' => $notification,
				'userId' => $this->userId,
				'userMgr' => $this->userMgr,
				'urlGenerator' => $this->urlGenerator,
				'avatarManager' => $this->avatarManager
			]);
		} else {
			User::checkLoggedIn();
			return new TemplateResponse('polls', 'no.acc.tmpl', []);
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @return TemplateResponse|RedirectResponse
	 */
	public function deletePoll($pollId) {
		$pollToDelete = $this->eventMapper->find($pollId);
		if ($this->userId !== $pollToDelete->getOwner() && !$this->groupManager->isAdmin($this->userId)) {
			return new TemplateResponse('polls', 'no.delete.tmpl');
		}
		$poll = new Event();
		$poll->setId($pollId);
		$this->commentMapper->deleteByPoll($pollId);
		$this->votesMapper->deleteByPoll($pollId);
		$this->optionsMapper->deleteByPoll($pollId);
		$this->eventMapper->delete($poll);
		$url = $this->urlGenerator->linkToRoute('polls.page.index');
		return new RedirectResponse($url);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $hash
	 * @return TemplateResponse
	 */
	public function editPoll($hash) {
		return new TemplateResponse('polls', 'create.tmpl', [
			'urlGenerator' => $this->urlGenerator,
 			'hash' => $hash
		]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function createPoll() {
		return new TemplateResponse('polls', 'create.tmpl',
			['urlGenerator' => $this->urlGenerator]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param string $userId
	 * @param string $answers
	 * @param string $options
	 * @param bool $receiveNotifications
	 * @param bool $changed
	 * @return RedirectResponse
	 */
	public function insertVote($pollId, $userId, $answers, $options, $receiveNotifications, $changed) {
		if ($this->userId !== null) {
			if ($receiveNotifications) {
				try {
					//check if user already set notification for this poll
					$this->notificationMapper->findByUserAndPoll($pollId, $userId);
				} catch (DoesNotExistException $e) {
					//insert if not exist
					$not = new Notification();
					$not->setUserId($userId);
					$not->setPollId($pollId);
					$this->notificationMapper->insert($not);
				}
			} else {
				try {
					//delete if entry is in db
					$not = $this->notificationMapper->findByUserAndPoll($pollId, $userId);
					$this->notificationMapper->delete($not);
				} catch (DoesNotExistException $e) {
					//doesn't exist in db, nothing to do
				}
			}
		}
		$poll = $this->eventMapper->find($pollId);

		if ($changed) {
			$options = json_decode($options);
			$answers = json_decode($answers);
			$count_options = count($options);
			$this->votesMapper->deleteByPollAndUser($pollId, $userId);

			for ($i = 0; $i < $count_options; $i++) {
				$vote = new Votes();
				$vote->setPollId($pollId);
				$vote->setUserId($userId);
				$vote->setVoteOptionText(htmlspecialchars($options[$i]));
				$vote->setVoteAnswer($answers[$i]);
				$this->votesMapper->insert($vote);

			}
			$this->sendNotifications($pollId, $userId);
		}
		$hash = $poll->getHash();
		$url = $this->urlGenerator->linkToRoute('polls.page.goto_poll', ['hash' => $hash]);
		return new RedirectResponse($url);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param string $userId
	 * @param string $commentBox
	 * @return JSONResponse
	 */
	public function insertComment($pollId, $userId, $commentBox) {
		$comment = new Comment();
		$comment->setPollId($pollId);
		$comment->setUserId($userId);
		$comment->setComment($commentBox);
		$comment->setDt(date('Y-m-d H:i:s'));
		$this->commentMapper->insert($comment);
		$this->sendNotifications($pollId, $userId);
		$timeStamp = time();
		$displayName = $userId;
		$user = $this->userMgr->get($userId);
		if ($user !== null) {
			$displayName = $user->getDisplayName();
		}
		return new JSONResponse(array(
			'userId' => $userId,
			'displayName' => $displayName,
			'timeStamp' => $timeStamp * 100,
			'date' => date('Y-m-d H:i:s', $timeStamp),
			'relativeNow' => $this->trans->t('just now'),
			'comment' => $commentBox
		));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $searchTerm
	 * @param string $groups
	 * @param string $users
	 * @return array
	 */
	public function search($searchTerm, $groups, $users) {
		return array_merge($this->searchForGroups($searchTerm, $groups), $this->searchForUsers($searchTerm, $users));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $searchTerm
	 * @param string $groups
	 * @return array
	 */
	public function searchForGroups($searchTerm, $groups) {
		$selectedGroups = json_decode($groups);
		$groups = $this->groupManager->search($searchTerm);
		$gids = array();
		$sgids = array();
		foreach ($selectedGroups as $sg) {
			$sgids[] = str_replace('group_', '', $sg);
		}
		foreach ($groups as $g) {
			$gids[] = $g->getGID();
		}
		$diffGids = array_diff($gids, $sgids);
		$gids = array();
		foreach ($diffGids as $g) {
			$gids[] = ['gid' => $g, 'isGroup' => true];
		}
		return $gids;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $searchTerm
	 * @param string $users
	 * @return array
	 */
	public function searchForUsers($searchTerm, $users) {
		$selectedUsers = json_decode($users);
		Util::writeLog('polls', print_r($selectedUsers, true), Util::ERROR);
		$userNames = $this->userMgr->searchDisplayName($searchTerm);
		$users = array();
		$sUsers = array();
		foreach ($selectedUsers as $su) {
			$sUsers[] = str_replace('user_', '', $su);
		}
		foreach ($userNames as $u) {
			$allreadyAdded = false;
			foreach ($sUsers as &$su) {
				if ($su === $u->getUID()) {
					unset($su);
					$allreadyAdded = true;
					break;
				}
			}
			if (!$allreadyAdded) {
				$users[] = array('uid' => $u->getUID(), 'displayName' => $u->getDisplayName(), 'isGroup' => false);
			} else {
				continue;
			}
		}
		return $users;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $username
	 * @return string
	 */
	public function getDisplayName($username) {
		return $this->userMgr->get($username)->getDisplayName();
	}

	/**
	 * @return \OCP\IGroup[]
	 */
	private function getGroups() {
		if (class_exists('\OC_Group')) {
			// Nextcloud <= 11, ownCloud
			return \OC_Group::getUserGroups($this->userId);
		}
		// Nextcloud >= 12
		$groups = $this->groupManager->getUserGroups(\OC::$server->getUserSession()->getUser());
		return array_map(function($group) {
			return $group->getGID();
		}, $groups);
	}

	/**
	 * Check if user has access to this poll
	 *
	 * @param Event $poll
	 * @return bool
	 */
	private function hasUserAccess($poll) {
		$access = $poll->getAccess();
		$owner = $poll->getOwner();
		if ($access === 'public' || $access === 'hidden') {
			return true;
		}
		if ($this->userId === null) {
			return false;
		}
		if ($access === 'registered') {
			return true;
		}
		if ($owner === $this->userId) {
			return true;
		}
		Util::writeLog('polls', $this->userId, Util::ERROR);
		$userGroups = $this->getGroups();
		$arr = explode(';', $access);
		foreach ($arr as $item) {
			if (strpos($item, 'group_') === 0) {
				$grp = substr($item, 6);
				foreach ($userGroups as $userGroup) {
					if ($userGroup === $grp) {
						return true;
					}
				}
			} else {
				if (strpos($item, 'user_') === 0) {
					$usr = substr($item, 5);
					if ($usr === $this->userId) {
						return true;
					}
				}
			}
		}
		return false;
	}
	/**
	 * Check if user is owner of this poll
	 *
	 * @param Event $poll
	 * @return bool
	 */

	private function userIsOwner($poll) {
		$owner = $poll->getOwner();

		if ($owner === $this->userId) {
			return true;
		}
		Util::writeLog('polls', $this->userId, Util::ERROR);
		return false;
	}
}
