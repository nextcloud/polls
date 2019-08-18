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
use OCP\AppFramework\Db\MultipleObjectsReturnedException;

use OCP\IRequest;
use OCP\ILogger;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\IURLGenerator;
use OCP\Mail\IMailer;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\Notification;
use OCA\Polls\Db\NotificationMapper;

class NotificationController extends Controller {

	private $userId;
	private $mapper;
	private $logger;

	private $eventMapper;

	private $config;
	private $urlGenerator;
	private $userMgr;
	private $trans;
	private $transFactory;
	private $mailer;

	/**
	 * NotificationController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param NotificationMapper $mapper
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param EventMapper $eventMapper
	 * @param IConfig $config
	 * @param IUserManager $userMgr
	 * @param IL10N $trans
	 * @param IFactory $transFactory
	 * @param IURLGenerator $urlGenerator
	 * @param IMailer $mailer
	 */

	public function __construct(
		string $appName,
		$UserId,
		NotificationMapper $mapper,
		IRequest $request,
		ILogger $logger,
		EventMapper $eventMapper,
		IConfig $config,
		IURLGenerator $urlGenerator,
		IUserManager $userMgr,
		IL10N $trans,
		IFactory $transFactory,
		IMailer $mailer

	) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->mapper = $mapper;
		$this->logger = $logger;
		$this->eventMapper = $eventMapper;

		$this->config = $config;
		$this->userMgr = $userMgr;
		$this->trans = $trans;
		$this->transFactory = $transFactory;
		$this->urlGenerator = $urlGenerator;
		$this->mailer = $mailer;

	}

	/**
	 * @NoAdminRequired
	 * @param Integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {

		if (!\OC::$server->getUserSession()->isLoggedIn()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		try {
			$notification = $this->mapper->findByUserAndPoll($pollId, $this->userId);
		} catch (MultipleObjectsReturnedException $e) {
			// should not happen, but who knows
		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}
		return new DataResponse(null, Http::STATUS_OK);
	}

	/**
	* @NoAdminRequired
	 * @param Integer $pollId
	 */
	public function set($pollId, $subscribed) {
		if ($subscribed) {
			$notification = new Notification();
			$notification->setPollId($pollId);
			$notification->setUserId($this->userId);
			$this->mapper->insert($notification);
			return true;
		} else {
			$this->mapper->unsubscribe($pollId, $this->userId);
			return false;
		}
	}

	/**
	 * @param int $pollId
	 * @param string $from
	 */
	private function sendNotifications($pollId, $from) {
		$poll = $this->eventMapper->find($pollId);
		$notifications = $this->mapper->findAllByPoll($pollId);
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
				$this->urlGenerator->linkToRoute('polls.page.vote',
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
				/** @scrutinizer ignore-type */ false
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

}
