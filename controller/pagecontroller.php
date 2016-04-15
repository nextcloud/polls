<?php
/**
 * ownCloud - polls
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 * @copyright Vinzenz Rosenkranz 2016
 */

namespace OCA\Polls\Controller;

use \OCA\Polls\Db\Access;
use \OCA\Polls\Db\Comment;
use \OCA\Polls\Db\Date;
use \OCA\Polls\Db\Event;
use \OCA\Polls\Db\Notification;
use \OCA\Polls\Db\Participation;
use \OCA\Polls\Db\ParticipationText;
use \OCA\Polls\Db\Text;

use \OCA\Polls\Db\AccessMapper;
use \OCA\Polls\Db\CommentMapper;
use \OCA\Polls\Db\DateMapper;
use \OCA\Polls\Db\EventMapper;
use \OCA\Polls\Db\NotificationMapper;
use \OCA\Polls\Db\ParticipationMapper;
use \OCA\Polls\Db\ParticipationTextMapper;
use \OCA\Polls\Db\TextMapper;
use \OCP\IUserManager;
use \OCP\IAvatarManager;
use \OCP\ILogger;
use \OCP\IL10N;
use \OCP\IRequest;
use \OCP\IURLGenerator;
use OCP\Security\ISecureRandom;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Http\RedirectResponse;
use \OCP\AppFramework\Controller;

class PageController extends Controller {

    private $userId;
    private $accessMapper;
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
    public function __construct($appName, IRequest $request,
                IUserManager $manager,
                IAvatarManager $avatarManager,
                ILogger $logger,
                IL10N $trans,
                IURLGenerator $urlGenerator,
                $userId,
                AccessMapper $accessMapper,
                CommentMapper $commentMapper,
                DateMapper $dateMapper,
                EventMapper $eventMapper,
                NotificationMapper $notificationMapper,
                ParticipationMapper $ParticipationMapper,
                ParticipationTextMapper $ParticipationTextMapper,
                TextMapper $textMapper) {
        parent::__construct($appName, $request);
        $this->manager = $manager;
        $this->avatarManager = $avatarManager;
        $this->logger = $logger;
        $this->trans = $trans;
        $this->urlGenerator = $urlGenerator;
        $this->userId = $userId;
        $this->accessMapper = $accessMapper;
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
     * CAUTION: the @Stuff turn off security checks, for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $polls = $this->eventMapper->findAll();
        $comments = $this->commentMapper->findDistinctByUser($this->userId);
        $partic = $this->participationMapper->findDistinctByUser($this->userId);
        $response = new TemplateResponse('polls', 'main.tmpl', ['polls' => $polls, 'comments' => $comments, 'participations' => $partic, 'userId' => $this->userId, 'userMgr' => $this->manager, 'urlGenerator' => $this->urlGenerator]);
        if (class_exists('OCP\AppFramework\Http\ContentSecurityPolicy')) {
            $csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
            $response->setContentSecurityPolicy($csp);
        }
        return $response;
    }

    private function sendNotifications($pollId, $from) {
        $poll = $this->eventMapper->find($pollId);
        $notifs = $this->notificationMapper->findAllByPoll($pollId);
        foreach($notifs as $notif) {
            if($from === $notif->getUserId()) continue;
            $email = \OC::$server->getConfig()->getUserValue($notif->getUserId(), 'settings', 'email');
            if(strlen($email) === 0 || !isset($email)) continue;
            $url = \OC::$server->getURLGenerator()->getAbsoluteURL(\OC::$server->getURLGenerator()->linkToRoute('polls.page.goto_poll', array('hash' => $poll->getHash())));

            $msg = $this->trans->t('Hello %s,<br/><br/><strong>%s</strong> participated in the poll \'%s\'.<br/><br/>To go directly to the poll, you can use this <a href="%s">link</a>', array(
                $this->userMgr->get($notif->getUserId())->getDisplayName(), $this->userMgr->get($from)->getDisplayName(), $poll->getTitle(), $url));

            $msg .= "<br/><br/>";

            $toname = $this->userMgr->get($notif->getUserId())->getDisplayName();
            $subject = $this->trans->t('ownCloud Polls - New Comment');
            $fromaddress = \OCP\Util::getDefaultEmailAddress('no-reply');
            $fromname = $this->trans->t("ownCloud Polls") . ' (' . $from . ')';

            try {
                $mailer = \OC::$server->getMailer();
                $message = $mailer->createMessage();
                $message->setSubject($subject);
                $message->setFrom(array($fromaddress => $fromname));
                $message->setTo(array($email => $toname));
                $message->setHtmlBody($msg);
                $mailer->send($message);
            } catch (\Exception $e) {
                $message = 'error sending mail to: ' . $toname . ' (' . $email . ')';
                \OCP\Util::writeLog("polls", $message, \OCP\Util::ERROR);
            }
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function gotoPoll($hash) {
        $poll = $this->eventMapper->findByHash($hash);
        if($poll->getType() === '0') {
            $dates = $this->dateMapper->findByPoll($poll->getId());
            $votes = $this->participationMapper->findByPoll($poll->getId());
        }
        else {
            $dates = $this->textMapper->findByPoll($poll->getId());
            $votes = $this->participationTextMapper->findByPoll($poll->getId());
        }
        $comments = $this->commentMapper->findByPoll($poll->getId());
        try {
            $notification = $this->notificationMapper->findByUserAndPoll($poll->getId(), $this->userId);
        } catch(\OCP\AppFramework\Db\DoesNotExistException $e) {
            $notification = null;
        }
        if($this->hasUserAccess($poll)) return new TemplateResponse('polls', 'goto.tmpl', ['poll' => $poll, 'dates' => $dates, 'comments' => $comments, 'votes' => $votes, 'notification' => $notification, 'userId' => $this->userId, 'userMgr' => $this->manager, 'urlGenerator' => $this->urlGenerator, 'avatarManager' => $this->avatarManager]);
        else {
            \OCP\User::checkLoggedIn();
            return new TemplateResponse('polls', 'no.acc.tmpl', []);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
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
     */
    public function editPoll($hash) {
        $poll = $this->eventMapper->findByHash($hash);
        if($this->userId !== $poll->getOwner()) return new TemplateResponse('polls', 'no.create.tmpl');
        if($poll->getType() === '0') $dates = $this->dateMapper->findByPoll($poll->getId());
        else $dates = $this->textMapper->findByPoll($poll->getId());
        return new TemplateResponse('polls', 'create.tmpl', ['poll' => $poll, 'dates' => $dates, 'userId' => $this->userId, 'userMgr' => $this->manager, 'urlGenerator' => $this->urlGenerator]);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updatePoll($pollId, $pollType, $pollTitle, $pollDesc, $userId, $chosenDates, $expireTs, $accessType, $accessValues) {
        $event = $this->eventMapper->find($pollId);
        $event->setTitle(htmlspecialchars($pollTitle));
        $event->setDescription(htmlspecialchars($pollDesc));

        if($accessType === 'select') {
            if (isset($accessValues)) {
                $accessValues = json_decode($accessValues);
                if($accessValues !== null) {
                    $groups = array();
                    $users = array();
                    if($accessValues->groups !== null) $groups = $accessValues->groups;
                    if($accessValues->users !== null) $users = $accessValues->users;
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
        if($expireTs !== null && $expireTs !== '') {
            $expire = date('Y-m-d H:i:s', $expireTs + 60*60*24); //add one day, so it expires at the end of a day
        }
        $event->setExpire($expire);

        $this->dateMapper->deleteByPoll($pollId);
        $this->textMapper->deleteByPoll($pollId);
        if($pollType === 'event') {
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
            foreach($chosenDates as $el) {
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
        return new TemplateResponse('polls', 'create.tmpl', ['userId' => $this->userId, 'userMgr' => $this->manager, 'urlGenerator' => $this->urlGenerator]);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function insertPoll($pollType, $pollTitle, $pollDesc, $userId, $chosenDates, $expireTs, $accessType, $accessValues) {
        $event = new Event();
        $event->setTitle(htmlspecialchars($pollTitle));
        $event->setDescription(htmlspecialchars($pollDesc));
        $event->setOwner($userId);
        $event->setCreated(date('Y-m-d H:i:s'));
        $event->setHash(\OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate(16,
			ISecureRandom::CHAR_DIGITS.
			ISecureRandom::CHAR_LOWER.
			ISecureRandom::CHAR_UPPER));

        $groups = $accessValues->groups;
        $users = $accessValues->users;
        if ($accessType === 'select') {
            if (isset($accessValues)) {
                $accessValues = json_decode($accessValues);
                if($accessValues !== null) {
                    $groups = array();
                    $users = array();
                    if($accessValues->groups !== null) $groups = $accessValues->groups;
                    if($accessValues->users !== null) $users = $accessValues->users;
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
        if($expireTs !== null && $expireTs !== '') {
            $expire = date('Y-m-d H:i:s', $expireTs + 60*60*24); //add one day, so it expires at the end of a day
        }
        $event->setExpire($expire);

        $poll_id = -1;
        if($pollType === 'event') {
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
            foreach($chosenDates as $el) {
                $text = new Text();
                $text->setText($el);
                $text->setPollId($poll_id);
                $this->textMapper->insert($text);
            }
        }
        $url = $this->urlGenerator->linkToRoute('polls.page.index');
        return new RedirectResponse($url);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function insertVote($pollId, $userId, $types, $dates, $notif, $changed) {
        if($this->userId !== null) {
            if($notif === 'true') {
                try {
                    //check if user already set notification for this poll
                    $this->notificationMapper->findByUserAndPoll($pollId, $userId);
                } catch(\OCP\AppFramework\Db\DoesNotExistException $e) {
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
                } catch(\OCP\AppFramework\Db\DoesNotExistException $e) {
                    //doesn't exist in db, nothing to do
                }
            }
        } else {
            $userId = $userId . ' (extern)';
        }
        $poll = $this->eventMapper->find($pollId);
        if($changed === 'true') {
            $dates = json_decode($dates);
            $types = json_decode($types);
            if($poll->getType() === '0') $this->participationMapper->deleteByPollAndUser($pollId, $userId);
            else $this->participationTextMapper->deleteByPollAndUser($pollId, $userId);
            for($i=0; $i<count($dates); $i++) {
                if($poll->getType() === '0') {
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
     */
    public function insertComment($pollId, $userId, $commentBox) {
        $comment = new Comment();
        $comment->setPollId($pollId);
        $comment->setUserId($userId);
        $comment->setComment($commentBox);
        $comment->setDt(date('Y-m-d H:i:s'));
        $this->commentMapper->insert($comment);
        $this->sendNotifications($pollId, $userId);
        $hash = $this->eventMapper->find($pollId)->getHash();
        $url = $this->urlGenerator->linkToRoute('polls.page.goto_poll', ['hash' => $hash]);
        return new RedirectResponse($url);
    }

    public function getPollsForUser() {
        return $this->eventMapper->findAllForUser($this->userId);
    }

    public function getPollsForUserWithInfo($user = null) {
        if($user === null) return $this->eventMapper->findAllForUserWithInfo($this->userId);
        else return $this->eventMapper->findAllForUserWithInfo($user);
    }

    private function hasUserAccess($poll) {
        $access = $poll->getAccess();
        if ($access === 'public') return true;
        if ($access === 'hidden') return true;
        if ($this->userId === null) return false;
        if ($access === 'registered') return true;
        if ($owner === $this->userId) return true;
        $user_groups = \OC_Group::getUserGroups($this->userId);
        $arr = explode(';', $access);
        foreach ($arr as $item) {
            if (strpos($item, 'group_') === 0) {
                $grp = substr($item, 6);
                foreach ($user_groups as $user_group) {
                    if ($user_group === $grp) return true;
                }
            }
            else if (strpos($item, 'user_') === 0) {
                $usr = substr($item, 5);
                if ($usr === \OCP\User::getUser()) return true;
            }
        }
        return false;
    }
}
