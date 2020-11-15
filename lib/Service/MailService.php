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

namespace OCA\Polls\Service;

use OCP\IUser;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Mail\IEMailTemplate;

use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\Log;
use OCA\Polls\Model\Contact;
use OCA\Polls\Model\Email;
use OCA\Polls\Model\Group;
use OCA\Polls\Model\User;

class MailService {

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IConfig */
	private $config;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IL10N */
	private $trans;

	/** @var IFactory */
	private $transFactory;

	/** @var IMailer */
	private $mailer;

	/** @var SubscriptionMapper */
	private $subscriptionMapper;

	/** @var ShareMapper */
	private $shareMapper;

	/** @var PollMapper */
	private $pollMapper;

	/** @var LogMapper */
	private $logMapper;

	/**
	 * MailService constructor.
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $trans
	 * @param IFactory $transFactory
	 * @param IMailer $mailer
	 * @param SubscriptionMapper $subscriptionMapper
	 * @param ShareMapper $shareMapper
	 * @param PollMapper $pollMapper
	 * @param LogMapper $logMapper
	 */

	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		IConfig $config,
		IURLGenerator $urlGenerator,
		IL10N $trans,
		IFactory $transFactory,
		IMailer $mailer,
		ShareMapper $shareMapper,
		SubscriptionMapper $subscriptionMapper,
		PollMapper $pollMapper,
		LogMapper $logMapper
	) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->urlGenerator = $urlGenerator;
		$this->trans = $trans;
		$this->transFactory = $transFactory;
		$this->mailer = $mailer;
		$this->shareMapper = $shareMapper;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->pollMapper = $pollMapper;
		$this->logMapper = $logMapper;
	}


	/**
	 * sendMail - Send eMail and evaluate recipient's mail address
	 * and displayname if $userId is a site user
	 * @param IEmailTemplate $emailTemplate
	 * @param String $userId
	 * @param String $emailAddress, ignored, when $userId is set
	 * @param String $displayName, ignored, when $userId is set
	 * @return String
	 */

	private function sendMail($emailTemplate, $emailAddress, $displayName) {
		if (!$emailAddress || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
			throw new \Exception('Invalid email address (' . $emailAddress . ')');
		}

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$emailAddress => $displayName]);
			$message->useTemplate($emailTemplate);
			$this->mailer->send($message);

			return null;
		} catch (\Exception $e) {
			\OC::$server->getLogger()->logException($e->getMessage(), ['app' => 'polls']);
			throw $e;
		}
	}

	/**
	 * @param string $token
	 */
	public function sendInvitation($token) {
		$share = $this->shareMapper->findByToken($token);
		$poll = $this->pollMapper->find($share->getPollId());
		$sentMails = [];
		$abortedMails = [];

		foreach ($share->getMembers() as $recipient) {
			//skip poll owner
			if ($recipient->getId() === $poll->getOwner()) {
				continue;
			}

			$emailTemplate = $this->generateInvitation($recipient, $poll, $share->getURL());

			try {
				$this->sendMail(
					$emailTemplate,
					$recipient->getEmailAddress(),
					$recipient->getDisplayName()
				);
				$share->setInvitationSent(time());
				$this->shareMapper->update($share);
				$sentMails[] = $recipient->getId();
			} catch (\Exception $e) {
				$abortedMails[] = $recipient->getId();
				\OC::$server->getLogger()->alert('Error sending Mail to ' . json_encode($recipient));
			}

		}
		return ['sentMails' => $sentMails, 'abortedMails' => $abortedMails];
	}

	public function sendNotifications() {
		$subscriptions = [];
		$log = $this->logMapper->findUnprocessedPolls();

		foreach ($log as $logItem) {
			$subscriptions = array_merge($subscriptions, $this->subscriptionMapper->findAllByPoll($logItem->getPollId()));
		}

		$log = $this->logMapper->findUnprocessed();

		foreach ($subscriptions as $subscription) {
			$poll = $this->pollMapper->find($subscription->getPollId());

			if ($this->userManager->get($subscription->getUserId()) instanceof IUser) {
				$recipient = new User($subscription->getUserId());
				$url = $this->urlGenerator->linkToRouteAbsolute(
					'polls.page.vote',
					['id' => $subscription->getPollId()]
				);
			} else {
				try {
					$share = $this->shareMapper->findByPollAndUser($subscription->getPollId(), $subscription->getUserId());
					$recipient = $share->getUserObject();
					$url = $share->getURL();
				} catch (\Exception $e) {
					continue;
				}
			}

			$emailTemplate = $this->generateNotification($recipient, $poll, $url, $log);

			try {
				$this->sendMail(
					$emailTemplate,
					$recipient->getEmailAddress(),
					$recipient->getDisplayName()
				);
			} catch (\Exception $e) {
				\OC::$server->getLogger()->alert('Error sending Mail to ' . $recipient->getId());
			}
		}
	}

	/**
	 * generateNotification
	 * @param UserGroupClass $recipient
	 * @param Poll $poll
	 * @return Object $emailTemplate
	 */

	private function generateNotification($recipient, $poll, $url, $log) {
		$owner = $poll->getOwnerUserObject();
		if ($recipient->getLanguage()) {
			$trans = $this->transFactory->get('polls', $recipient->getLanguage());
		} else {
			$trans = $this->transFactory->get('polls', $owner->getLanguage());
		}
		$emailTemplate = $this->mailer->createEMailTemplate('polls.Notification', [
			'title' => $poll->getTitle(),
			'link' => $url
		]);

		$emailTemplate->setSubject($trans->t('Polls App - New Activity'));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($trans->t('Polls App - New Activity'), false);
		$emailTemplate->addBodyText(str_replace(
			['{title}'],
			[$poll->getTitle()],
			$trans->t('"{title}" had recent activity: ')
		));
		foreach ($log as $logItem) {

			if ($logItem->getPollId() === $poll->getId()) {
				if ($poll->getAnonymous() || $poll->getShowResults() !== "always") {
					$displayUser = $trans->t('A user');
				} elseif ($this->userManager->get($logItem->getUserId()) instanceof IUser) {
						$actor = new User($subscription->getUserId());
						$displayUser = $actor->getDisplayName();
				} else {
					try {
						$share = $this->shareMapper->findByPollAndUser($subscription->getPollId(), $logItem->getUserId());
						$displayUser = $share->getUserObject()->getDisplayName();
					} catch (\Exception $e) {
						$displayUser = $logItem->getUserId();
					}
				}

				if ($logItem->getMessage()) {
					$emailTemplate->addBodyText($logItem->getMessage());
				} elseif ($logItem->getMessageId() === Log::MSG_ID_SETVOTE) {
					$emailTemplate->addBodyText($trans->t(
						'- %s voted.',
						[$displayUser]
					));
				} elseif ($logItem->getMessageId() === Log::MSG_ID_UPDATEPOLL) {
					$emailTemplate->addBodyText($trans->t(
						'- Updated poll configuration. Please check your votes.',
						[$displayUser]
					));
				} elseif ($logItem->getMessageId() === Log::MSG_ID_DELETEPOLL) {
					$emailTemplate->addBodyText($trans->t(
						'- The poll got deleted.',
						[$displayUser]
					));
				} elseif ($logItem->getMessageId() === Log::MSG_ID_RESTOREPOLL) {
					$emailTemplate->addBodyText($trans->t(
						'- The poll got restored.',
						[$displayUser]
					));
				} elseif ($logItem->getMessageId() === Log::MSG_ID_EXPIREPOLL) {
					$emailTemplate->addBodyText($trans->t(
						'- The poll closed.',
						[$displayUser]
					));
				} elseif ($logItem->getMessageId() === Log::MSG_ID_ADDOPTION) {
					$emailTemplate->addBodyText($trans->t(
						'- A vote option was added.',
						[$displayUser]
					));
				} elseif ($logItem->getMessageId() === Log::MSG_ID_DELETEOPTION) {
					$emailTemplate->addBodyText($trans->t(
						'- A vote option was removed.',
						[$displayUser]
					));
				} else {
					$emailTemplate->addBodyText(
						$logItem->getMessageId() . " (" . $displayUser . ")"
					);
				}
			}

			$logItem->setProcessed(time());
			$this->logMapper->update($logItem);
		}

		$emailTemplate->addBodyButton(
			htmlspecialchars($trans->t('Go to poll')),
			$url,
			/** @scrutinizer ignore-type */ false
		);
		$emailTemplate->addFooter($trans->t('This email is sent to you, because you subscribed to notifications of this poll. To opt out, visit the poll and remove your subscription.'));

		return $emailTemplate;
	}

		/**
		 * generateInvitation
		 * @param UserGroupClass $recipient
		 * @param Poll $poll
		 * @return Object $emailTemplate
		 */

		private function generateInvitation($recipient, $poll, $url) {
			$owner = $poll->getOwnerUserObject();
			if ($recipient->getLanguage()) {
				$trans = $this->transFactory->get('polls', $recipient->getLanguage());
			} else {
				$trans = $this->transFactory->get('polls', $owner->getLanguage());
			}

			$emailTemplate = $this->mailer->createEMailTemplate('polls.Invitation', [
				'owner' => $owner->getDisplayName(),
				'title' => $poll->getTitle(),
				'link' => $url
			]);

			$emailTemplate->setSubject($trans->t('Poll invitation "%s"', $poll->getTitle()));
			$emailTemplate->addHeader();
			$emailTemplate->addHeading($trans->t('Poll invitation "%s"', $poll->getTitle()), false);
			$emailTemplate->addBodyText(str_replace(
				['{owner}', '{title}'],
				[$owner->getDisplayName(), $poll->getTitle()],
				$trans->t('{owner} invited you to take part in the poll "{title}"')
			));
			$emailTemplate->addBodyText($poll->getDescription());
			$emailTemplate->addBodyButton(
				htmlspecialchars($trans->t('Go to poll')),
				$url
			);
			$emailTemplate->addBodyText($trans->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar: '));
			$emailTemplate->addBodyText($url);
			$emailTemplate->addBodyText($trans->t('Do not share this link with other people, because it is connected to your votes.'));
			$emailTemplate->addFooter($trans->t('This email is sent to you, because you are invited to vote in this poll by the poll owner. At least your name or your email address is recorded in this poll. If you want to get removed from this poll, contact the site administrator or the initiator of this poll, where the mail is sent from.'));

			return $emailTemplate;
		}

}
