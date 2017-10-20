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
use OCA\Polls\Db\Date;
use OCA\Polls\Db\Event;
use OCA\Polls\Db\Notification;
use OCA\Polls\Db\Participation;
use OCA\Polls\Db\ParticipationText;
use OCA\Polls\Db\Text;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\DateMapper;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\NotificationMapper;
use OCA\Polls\Db\ParticipationMapper;
use OCA\Polls\Db\ParticipationTextMapper;
use OCA\Polls\Db\TextMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IAvatarManager;
use OCP\ILogger;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Controller;
use OCP\User;
use OCP\Util;

class PageController extends Controller {

	private $userId;
	private $commentMapper;
	private $dateMapper;
	private $eventMapper;
	private $notificationMapper;
	private $participationMapper;
	private $participationTextMapper;
	private $textMapper;
	private $urlGenerator;
	private $manager;
	private $avatarManager;
	private $logger;
	private $trans;
	private $userMgr;
	private $groupManager;

	/**
	 * PageController constructor.
	 * @param $appName
	 * @param IRequest $request
	 * @param IUserManager $manager
	 * @param IGroupManager $groupManager
	 * @param IAvatarManager $avatarManager
	 * @param ILogger $logger
	 * @param IL10N $trans
	 * @param IURLGenerator $urlGenerator
	 * @param $userId
	 * @param CommentMapper $commentMapper
	 * @param DateMapper $dateMapper
	 * @param EventMapper $eventMapper
	 * @param NotificationMapper $notificationMapper
	 * @param ParticipationMapper $ParticipationMapper
	 * @param ParticipationTextMapper $ParticipationTextMapper
	 * @param TextMapper $textMapper
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IUserManager $manager,
		IGroupManager $groupManager,
		IAvatarManager $avatarManager,
		ILogger $logger,
		IL10N $trans,
		IURLGenerator $urlGenerator,
		$userId,
		CommentMapper $commentMapper,
		DateMapper $dateMapper,
		EventMapper $eventMapper,
		NotificationMapper $notificationMapper,
		ParticipationMapper $ParticipationMapper,
		ParticipationTextMapper $ParticipationTextMapper,
		TextMapper $textMapper
	) {
		parent::__construct($appName, $request);
		$this->manager = $manager;
		$this->groupManager = $groupManager;
		$this->avatarManager = $avatarManager;
		$this->logger = $logger;
		$this->trans = $trans;
		$this->urlGenerator = $urlGenerator;
		$this->userId = $userId;
		$this->commentMapper = $commentMapper;
		$this->dateMapper = $dateMapper;
		$this->eventMapper = $eventMapper;
		$this->notificationMapper = $notificationMapper;
		$this->participationMapper = $ParticipationMapper;
		$this->participationTextMapper = $ParticipationTextMapper;
		$this->textMapper = $textMapper;
		$this->userMgr = \OC::$server->getUserManager();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		$polls = $this->eventMapper->findAllForUserWithInfo($this->userId);
		$comments = $this->commentMapper->findDistinctByUser($this->userId);
		$partic = $this->participationMapper->findDistinctByUser($this->userId);
		$particText = $this->participationTextMapper->findDistinctByUser($this->userId);
		$response = new TemplateResponse('polls', 'main.tmpl', [
			'polls' => $polls,
			'comments' => $comments,
			'participations' => $partic,
			'participations_text' => $particText,
			'userId' => $this->userId,
			'userMgr' => $this->manager,
			'urlGenerator' => $this->urlGenerator
		]);
		if (class_exists('OCP\AppFramework\Http\ContentSecurityPolicy')) {
			$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
			$response->setContentSecurityPolicy($csp);
		}
		return $response;
	}

	/**
	 * @param string $pollId
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
			if (strlen($email) === 0 || !isset($email)) {
				continue;
			}
			$url = \OC::$server->getURLGenerator()->getAbsoluteURL(\OC::$server->getURLGenerator()->linkToRoute('polls.page.goto_poll',
				array('hash' => $poll->getHash())));

			$recUser = $this->userMgr->get($notification->getUserId());
			$sendUser = $this->userMgr->get($from);
			$rec = "";
			if ($recUser !== null) {
				$rec = $recUser->getDisplayName();
			}
			if ($sendUser !== null) {
				$sender = $sendUser->getDisplayName();
			} else {
				$sender = $from;
			}
			$msg = $this->trans->t('Hello %s,<br/><br/><strong>%s</strong> participated in the poll \'%s\'.<br/><br/>To go directly to the poll, you can use this <a href="%s">link</a>',
				array(
					$rec,
					$sender,
					$poll->getTitle(),
					$url
				));

			$msg .= "<br/><br/>";

			$toName = $this->userMgr->get($notification->getUserId())->getDisplayName();
			$subject = $this->trans->t('Polls App - New Comment');
			$fromAddress = Util::getDefaultEmailAddress('no-reply');
			$fromName = $this->trans->t("Polls App") . ' (' . $from . ')';

			try {
				$mailer = \OC::$server->getMailer();
				$message = $mailer->createMessage();
				$message->setSubject($subject);
				$message->setFrom(array($fromAddress => $fromName));
				$message->setTo(array($email => $toName));
				$message->setHtmlBody($msg);
				$mailer->send($message);
			} catch (\Exception $e) {
				$message = 'Error sending mail to: ' . $toName . ' (' . $email . ')';
				Util::writeLog("polls", $message, Util::ERROR);
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
		$poll = $this->eventMapper->findByHash($hash);
		if ($poll->getType() == '0') {
			$dates = $this->dateMapper->findByPoll($poll->getId());
			$votes = $this->participationMapper->findByPoll($poll->getId());
		} else {
			$dates = $this->textMapper->findByPoll($poll->getId());
			$votes = $this->participationTextMapper->findByPoll($poll->getId());
		}
		$comments = $this->commentMapper->findByPoll($poll->getId());
		try {
			$notification = $this->notificationMapper->findByUserAndPoll($poll->getId(), $this->userId);
		} catch (DoesNotExistException $e) {
			$notification = null;
		}
		if ($this->hasUserAccess($poll)) {
			return new TemplateResponse('polls', 'goto.tmpl', [
				'poll' => $poll,
				'dates' => $dates,
				'comments' => $comments,
				'votes' => $votes,
				'notification' => $notification,
				'userId' => $this->userId,
				'userMgr' => $this->manager,
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
	 * @param string $pollId
	 * @return RedirectResponse
	 */
	public function deletePoll($pollId) {
		$poll = new Event();
		$poll->setId($pollId);
		$this->eventMapper->delete($poll);
		$this->textMapper->deleteByPoll($pollId);
		$this->dateMapper->deleteByPoll($pollId);
		$this->participationMapper->deleteByPoll($pollId);
		$this->participationTextMapper->deleteByPoll($pollId);
		$this->commentMapper->deleteByPoll($pollId);
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
		$poll = $this->eventMapper->findByHash($hash);
		if ($this->userId !== $poll->getOwner()) {
			return new TemplateResponse('polls', 'no.create.tmpl');
		}
		if ($poll->getType() == '0') {
			$dates = $this->dateMapper->findByPoll($poll->getId());
		} else {
			$dates = $this->textMapper->findByPoll($poll->getId());
		}
		return new TemplateResponse('polls', 'create.tmpl', [
			'poll' => $poll,
			'dates' => $dates,
			'userId' => $this->userId,
			'userMgr' => $this->manager,
			'urlGenerator' => $this->urlGenerator
		]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $pollId
	 * @param $pollType
	 * @param $pollTitle
	 * @param $pollDesc
	 * @param $userId
	 * @param $chosenDates
	 * @param $expireTs
	 * @param $accessType
	 * @param $accessValues
	 * @param $isAnonymous
	 * @param $hideNames
	 * @return RedirectResponse
	 */
	public function updatePoll(
		$pollId,
		$pollType,
		$pollTitle,
		$pollDesc,
		$userId,
		$chosenDates,
		$expireTs,
		$accessType,
		$accessValues,
		$isAnonymous,
		$hideNames
	) {
		$event = $this->eventMapper->find($pollId);
		$event->setTitle(htmlspecialchars($pollTitle));
		$event->setDescription(htmlspecialchars($pollDesc));
		$event->setIsAnonymous($isAnonymous ? 1 : 0);
		$event->setFullAnonymous($isAnonymous && $hideNames ? 1 : 0);

		if ($accessType === 'select') {
			if (isset($accessValues)) {
				$accessValues = json_decode($accessValues);
				if ($accessValues !== null) {
					$groups = array();
					$users = array();
					if ($accessValues->groups !== null) {
						$groups = $accessValues->groups;
					}
					if ($accessValues->users !== null) {
						$users = $accessValues->users;
					}
					$accessType = '';
					foreach ($groups as $gid) {
						$accessType .= $gid . ';';
					}
					foreach ($users as $uid) {
						$accessType .= $uid . ';';
					}
				}
			}
		}
		$event->setAccess($accessType);

		$chosenDates = json_decode($chosenDates);

		$expire = null;
		if ($expireTs !== null && $expireTs !== '') {
			$expire = date('Y-m-d H:i:s', $expireTs + 60 * 60 * 24); //add one day, so it expires at the end of a day
		}
		$event->setExpire($expire);

		$this->dateMapper->deleteByPoll($pollId);
		$this->textMapper->deleteByPoll($pollId);
		if ($pollType === 'event') {
			$event->setType(0);
			$this->eventMapper->update($event);
			sort($chosenDates);
			foreach ($chosenDates as $el) {
				$date = new Date();
				$date->setPollId($pollId);
				$date->setDt(date('Y-m-d H:i:s', $el));
				$this->dateMapper->insert($date);
			}
		} else {
			$event->setType(1);
			$this->eventMapper->update($event);
			foreach ($chosenDates as $el) {
				$text = new Text();
				$text->setText($el);
				$text->setPollId($pollId);
				$this->textMapper->insert($text);
			}
		}
		$url = $this->urlGenerator->linkToRoute('polls.page.index');
		return new RedirectResponse($url);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function createPoll() {
		return new TemplateResponse('polls', 'create.tmpl',
			['userId' => $this->userId, 'userMgr' => $this->manager, 'urlGenerator' => $this->urlGenerator]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $pollType
	 * @param $pollTitle
	 * @param $pollDesc
	 * @param $userId
	 * @param $chosenDates
	 * @param $expireTs
	 * @param $accessType
	 * @param $accessValues
	 * @param $isAnonymous
	 * @param $hideNames
	 * @return RedirectResponse
	 */
	public function insertPoll(
		$pollType,
		$pollTitle,
		$pollDesc,
		$userId,
		$chosenDates,
		$expireTs,
		$accessType,
		$accessValues,
		$isAnonymous,
		$hideNames
	) {
		$event = new Event();
		$event->setTitle(htmlspecialchars($pollTitle));
		$event->setDescription(htmlspecialchars($pollDesc));
		$event->setOwner($userId);
		$event->setCreated(date('Y-m-d H:i:s'));
		$event->setHash(\OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate(16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER));
		$event->setIsAnonymous($isAnonymous ? 1 : 0);
		$event->setFullAnonymous($isAnonymous && $hideNames ? 1 : 0);

		if ($accessType === 'select') {
			if (isset($accessValues)) {
				$accessValues = json_decode($accessValues);
				if ($accessValues !== null) {
					$groups = array();
					$users = array();
					if ($accessValues->groups !== null) {
						$groups = $accessValues->groups;
					}
					if ($accessValues->users !== null) {
						$users = $accessValues->users;
					}
					$accessType = '';
					foreach ($groups as $gid) {
						$accessType .= $gid . ';';
					}
					foreach ($users as $uid) {
						$accessType .= $uid . ';';
					}
				}
			}
		}
		$event->setAccess($accessType);

		$chosenDates = json_decode($chosenDates);

		$expire = null;
		if ($expireTs !== null && $expireTs !== '') {
			$expire = date('Y-m-d H:i:s', $expireTs + 60 * 60 * 24); //add one day, so it expires at the end of a day
		}
		$event->setExpire($expire);

		if ($pollType === 'event') {
			$event->setType(0);
			$ins = $this->eventMapper->insert($event);
			$poll_id = $ins->getId();
			sort($chosenDates);
			foreach ($chosenDates as $el) {
				$date = new Date();
				$date->setPollId($poll_id);
				$date->setDt(date('Y-m-d H:i:s', $el));
				$this->dateMapper->insert($date);
			}
		} else {
			$event->setType(1);
			$ins = $this->eventMapper->insert($event);
			$poll_id = $ins->getId();
			$cnt = 1;
			foreach ($chosenDates as $el) {
				$text = new Text();
				$text->setText($el . '_' . $cnt);
				$text->setPollId($poll_id);
				$this->textMapper->insert($text);
				$cnt++;
			}
		}
		$url = $this->urlGenerator->linkToRoute('polls.page.index');
		return new RedirectResponse($url);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param $pollId
	 * @param $userId
	 * @param $types
	 * @param $dates
	 * @param $receiveNotifications
	 * @param $changed
	 * @return RedirectResponse
	 */
	public function insertVote($pollId, $userId, $types, $dates, $receiveNotifications, $changed) {
		if ($this->userId !== null) {
			if ($receiveNotifications === 'true') {
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
		if ($changed === 'true') {
			$dates = json_decode($dates);
			$types = json_decode($types);
			$count_dates = count($dates);
			if ($poll->getType() == '0') {
				$this->participationMapper->deleteByPollAndUser($pollId, $userId);
			} else {
				$this->participationTextMapper->deleteByPollAndUser($pollId, $userId);
			}
			for ($i = 0; $i < $count_dates; $i++) {
				if ($poll->getType() == '0') {
					$part = new Participation();
					$part->setPollId($pollId);
					$part->setUserId($userId);
					$part->setDt(date('Y-m-d H:i:s', $dates[$i]));
					$part->setType($types[$i]);
					$this->participationMapper->insert($part);
				} else {
					$part = new ParticipationText();
					$part->setPollId($pollId);
					$part->setUserId($userId);
					$part->setText($dates[$i]);
					$part->setType($types[$i]);
					$this->participationTextMapper->insert($part);
				}

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
	 * @param $pollId
	 * @param $userId
	 * @param $commentBox
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
		if ($this->manager->get($userId) !== null) {
			$newUserId = $this->manager->get($userId)->getDisplayName();
		} else {
			$newUserId = $userId;
		}
		return new JSONResponse(array(
			'comment' => $commentBox,
			'date' => date('Y-m-d H:i:s'),
			'userName' => $newUserId
		));
	}

// ===============> Not used?
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $searchTerm
	 * @param $groups
	 * @param $users
	 * @return array
	 */
/*	 public function search($searchTerm, $groups, $users) {
		return array_merge($this->searchForGroups($searchTerm, $groups), $this->searchForUsers($searchTerm, $users));
	}
 */
// ===============> Not used?
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $searchTerm
	 * @param $groups
	 * @return array
	 */
/*	 public function searchForGroups($searchTerm, $groups) {
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
 */
// ===============> Not used?
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $searchTerm
	 * @param $users
	 * @return array
	 */
/*	 public function searchForUsers($searchTerm, $users) {
		$selectedUsers = json_decode($users);
		Util::writeLog("polls", print_r($selectedUsers, true), Util::ERROR);
		$userNames = $this->userMgr->searchDisplayName($searchTerm);
		$users = array();
		$sUsers = array();
		foreach ($selectedUsers as $su) {
			$sUsers[] = str_replace('user_', '', $su);
		}
		foreach ($userNames as $u) {
			$alreadyAdded = false;
			foreach ($sUsers as &$su) {
				if ($su === $u->getUID()) {
					unset($su);
					$alreadyAdded = true;
					break;
				}
			}
			if (!$alreadyAdded) {
				$users[] = array('uid' => $u->getUID(), 'displayName' => $u->getDisplayName(), 'isGroup' => false);
			} else {
				continue;
			}
		}
		return $users;
	}
 */
// ===============> Not used?
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $username
	 * @return string
	 */
/*	 public function getDisplayName($username) {
		return $this->manager->get($username)->getDisplayName();
	}
 */
// ===============> Not used?
	/**
	 * @return Event[]
	 */
/*	 public function getPollsForUser() {
		return $this->eventMapper->findAllForUser($this->userId);
	}
 */
// ===============> Not used?
	/**
	 * @param $user
	 * @return Event[]
	 */
/*	 public function getPollsForUserWithInfo($user = null) {
		if ($user === null) {
			return $this->eventMapper->findAllForUserWithInfo($this->userId);
		} else {
			return $this->eventMapper->findAllForUserWithInfo($user);
		}
	}
 */
// ===============> Not used?
	/**
	 * @return array
	 */
/*	 public function getGroups() {
		// $this->requireLogin();
		if (class_exists('\OC_Group', true)) {
			// Nextcloud <= 11, ownCloud
			return \OC_Group::getUserGroups($this->userId);
		}
		// Nextcloud >= 12
		$groups = \OC::$server->getGroupManager()->getUserGroups(\OC::$server->getUserSession()->getUser());
		return array_map(function ($group) {
			return $group->getGID();
		}, $groups);
	}
 */
	/**
	 * @param $poll
	 * @return bool
	 */
	private function hasUserAccess($poll) {
		$access = $poll->getAccess();
		$owner = $poll->getOwner();
		if ($access === 'public') {
			return true;
		}
		if ($access === 'hidden') {
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
		Util::writeLog("polls", $this->userId, Util::ERROR);
		$user_groups = $this->getGroups();
		$arr = explode(';', $access);
		foreach ($arr as $item) {
			if (strpos($item, 'group_') === 0) {
				$grp = substr($item, 6);
				foreach ($user_groups as $user_group) {
					if ($user_group === $grp) {
						return true;
					}
				}
			} else {
				if (strpos($item, 'user_') === 0) {
					$usr = substr($item, 5);
					if ($usr === User::getUser()) {
						return true;
					}
				}
			}
		}
		return false;
	}
}
