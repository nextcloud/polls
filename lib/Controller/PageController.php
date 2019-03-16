<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Notification;
use OCA\Polls\Db\NotificationMapper;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\ISecureRandom;
use OCP\User; //To do: replace according to API
use OCP\Util;

class PageController extends Controller {

	private $userId;
	private $config;
	private $commentMapper;
	private $eventMapper;
	private $notificationMapper;
	private $optionMapper;
	private $voteMapper;
	private $urlGenerator;
	private $userMgr;
	private $avatarManager;
	private $logger;
	private $trans;
	private $transFactory;
	private $groupManager;
	private $mailer;

	/**
	 * PageController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param IUserManager $userMgr
	 * @param IGroupManager $groupManager
	 * @param IAvatarManager $avatarManager
	 * @param ILogger $logger
	 * @param IL10N $trans
	 * @param IFactory $transFactory
	 * @param IURLGenerator $urlGenerator
	 * @param string $userId
	 * @param CommentMapper $commentMapper
	 * @param OptionMapper $optionMapper
	 * @param EventMapper $eventMapper
	 * @param NotificationMapper $notificationMapper
	 * @param VoteMapper $VoteMapper
	 * @param IMailer $mailer
	 */
	public function __construct(
		$appName,
		IRequest $request,
		IConfig $config,
		IUserManager $userMgr,
		IGroupManager $groupManager,
		IAvatarManager $avatarManager,
		ILogger $logger,
		IL10N $trans,
		IFactory $transFactory,
		IURLGenerator $urlGenerator,
		$userId,
		CommentMapper $commentMapper,
		OptionMapper $optionMapper,
		EventMapper $eventMapper,
		NotificationMapper $notificationMapper,
		VoteMapper $VoteMapper,
		IMailer $mailer
	) {
		parent::__construct($appName, $request);
		$this->request = $request;
		$this->config = $config;
		$this->userMgr = $userMgr;
		$this->groupManager = $groupManager;
		$this->avatarManager = $avatarManager;
		$this->logger = $logger;
		$this->trans = $trans;
		$this->transFactory = $transFactory;
		$this->urlGenerator = $urlGenerator;
		$this->userId = $userId;
		$this->commentMapper = $commentMapper;
		$this->optionMapper = $optionMapper;
		$this->eventMapper = $eventMapper;
		$this->notificationMapper = $notificationMapper;
		$this->voteMapper = $VoteMapper;
		$this->mailer = $mailer;
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function index() {
		return new TemplateResponse('polls', 'polls.tmpl',
		['urlGenerator' => $this->urlGenerator]);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function createPoll() {
		return new TemplateResponse('polls', 'polls.tmpl',
		['urlGenerator' => $this->urlGenerator]);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function clonePoll() {
		return new TemplateResponse('polls', 'polls.tmpl',
		['urlGenerator' => $this->urlGenerator]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $hash
	 * @return TemplateResponse
	 */
	public function editPoll($hash) {
		return new TemplateResponse('polls', 'polls.tmpl', [
			'urlGenerator' => $this->urlGenerator,
 			'hash' => $hash
		]);
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
			$recUser = $this->userMgr->get($notification->getUserId());
			if (!$recUser instanceof IUser) {
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

			$sendUser = $this->userMgr->get($from);
			$sender = $from;
			if ($sendUser instanceof IUser) {
				$sender = $sendUser->getDisplayName();
			}

			$lang = $this->config->getUserValue($notification->getUserId(), 'core', 'lang');
			$trans = $this->transFactory->get('polls', $lang);
			$emailTemplate = $this->mailer->createEMailTemplate('polls.Notification', [
				'user' => $sender,
				'title' => $poll->getTitle(),
				'link' => $url,
			]);
			$emailTemplate->setSubject($trans->t('Polls App - New Activity'));
			$emailTemplate->addHeader();
			$emailTemplate->addHeading($trans->t('Polls App - New Activity'), false);

			$emailTemplate->addBodyText(str_replace(
				['{user}', '{title}'],
				[$sender, $poll->getTitle()],
				$trans->t('{user} participated in the poll "{title}"')
			));

			$emailTemplate->addBodyButton(
				htmlspecialchars($trans->t('Go to poll')),
				$url,
				false
			);

			$emailTemplate->addFooter();
			try {
				$message = $this->mailer->createMessage();
				$message->setTo([$email => $recUser->getDisplayName()]);
				$message->useTemplate($emailTemplate);
				$this->mailer->send($message);
			} catch (\Exception $e) {
				$this->logger->logException($e, ['app' => 'polls']);
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
		$options = $this->optionMapper->findByPoll($poll->getId());
		$votes = $this->voteMapper->findByPoll($poll->getId());
		$participants = $this->voteMapper->findParticipantsByPoll($poll->getId());
		$comments = $this->commentMapper->findByPoll($poll->getId());

		try {
			$notification = $this->notificationMapper->findByUserAndPoll($poll->getId(), $this->userId);
		} catch (DoesNotExistException $e) {
			$notification = null;
		}
		if ($this->hasUserAccess($poll)) {
			return new TemplateResponse('polls', 'vote.tmpl', [
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
		$this->voteMapper->deleteByPoll($pollId);
		$this->optionMapper->deleteByPoll($pollId);
		$this->eventMapper->delete($poll);
		$url = $this->urlGenerator->linkToRoute('polls.page.index');
		return new RedirectResponse($url);
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
			$this->voteMapper->deleteByPollAndUser($pollId, $userId);

			for ($i = 0; $i < $count_options; $i++) {
				$vote = new Vote();
				$vote->setPollId($pollId);
				$vote->setUserId($userId);
				$vote->setVoteOptionText(htmlspecialchars($options[$i]));
				$vote->setVoteAnswer($answers[$i]);
				$this->voteMapper->insert($vote);

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
