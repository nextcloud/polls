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

use Psr\Log\LoggerInterface;
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
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\Log;
use OCA\Polls\Model\UserGroupClass;
use OCA\Polls\Model\User;

class MailService {


	/** @var LoggerInterface */
	private $logger;

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

	public function __construct(
		string $appname,
		LoggerInterface $logger,
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
		$this->appName = $appname;
		$this->logger = $logger;
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

	private function sendMail(IEmailTemplate $emailTemplate, string $emailAddress, string $displayName): void {
		if (!$emailAddress || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
			throw new \Exception('Invalid email address (' . $emailAddress . ')');
		}

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$emailAddress => $displayName]);
			$message->useTemplate($emailTemplate);
			$this->mailer->send($message);
		} catch (\Exception $e) {
			$this->logger->alert($e->getMessage());
			throw $e;
		}
	}

	public function resolveEmailAddress(int $pollId, string $userId): string {
		if ($this->userManager->get($userId) instanceof IUser) {
			return \OC::$server->getConfig()->getUserValue($userId, 'settings', 'email');
		}

		// if $userId is no site user, eval via shares
		try {
			$share = $this->shareMapper->findByPollAndUser($pollId, $userId);
			if ($share->getEmailAddress()) {
				return $share->getEmailAddress();
			}
		} catch (\Exception $e) {
			// catch silently
		}
		return $userId;
	}

	public function resendInvitation(string $token): Share {
		$share = $this->shareMapper->findByToken($token);
		$poll = $this->pollMapper->find($share->getPollId());
		$recipient = $share->getUserObject();
		$emailTemplate = $this->generateInvitation($recipient, $poll, $share->getURL());
		$this->sendMail(
			$emailTemplate,
			$recipient->getEmailAddress(),
			$recipient->getDisplayName()
		);
		$share->setInvitationSent(time());
		return $this->shareMapper->update($share);
	}

	public function sendInvitation(string $token): array {
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
				$this->logger->error('Error sending Mail to ' . json_encode($recipient));
			}
		}
		return ['sentMails' => $sentMails, 'abortedMails' => $abortedMails];
	}

	public function sendNotifications(): void {
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
				$this->logger->error('Error sending Mail to ' . $recipient->getId());
			}
		}
	}

	private function getLogString(Log $logItem, string $displayName): string {
		switch ($logItem->getMessageId()) {
			case Log::MSG_ID_SETVOTE:
				return $this->trans->t('- %s voted.', [$displayName]);
			case Log::MSG_ID_UPDATEPOLL:
				return $this->trans->t('- Updated poll configuration. Please check your votes.');
			case Log::MSG_ID_DELETEPOLL:
				return $this->trans->t('- The poll got deleted.');
			case Log::MSG_ID_RESTOREPOLL:
				return $this->trans->t('- The poll got restored.');
			case Log::MSG_ID_EXPIREPOLL:
				return $this->trans->t('- The poll was closed.');
			case Log::MSG_ID_ADDOPTION:
				return $this->trans->t('- A vote option was added.');
			case Log::MSG_ID_DELETEOPTION:
				return $this->trans->t('- A vote option was removed.');
			case Log::MSG_ID_OWNERCHANGE:
				return $this->trans->t('- The poll owner changed.');
			case Log::MSG_ID_ADDPOLL:
				return $this->trans->t('- %s created the poll.', [$displayName]);
			default:
				return $logItem->getMessageId() . " (" . $displayName . ")";
		}
	}

	private function generateNotification(UserGroupClass $recipient, Poll $poll, string $url, array $log): IEMailTemplate {
		$owner = $poll->getOwnerUserObject();
		$this->trans = $this->transFactory->get('polls', $recipient->getLanguage() ? $recipient->getLanguage() : $owner->getLanguage());
		$emailTemplate = $this->mailer->createEMailTemplate('polls.Notification', [
			'title' => $poll->getTitle(),
			'link' => $url
		]);

		$emailTemplate->setSubject($this->trans->t('Polls App - New Activity'));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->trans->t('Polls App - New Activity'), false);
		$emailTemplate->addBodyText(str_replace(
			['{title}'],
			[$poll->getTitle()],
			$this->trans->t('"{title}" had recent activity: ')
		));
		foreach ($log as $logItem) {
			if ($logItem->getPollId() === $poll->getId()) {
				if ($poll->getAnonymous() || $poll->getShowResults() !== "always") {
					$displayName = $this->trans->t('A user');
				} elseif ($this->userManager->get($logItem->getUserId()) instanceof IUser) {
					$actor = new User($logItem->getUserId());
					$displayName = $actor->getDisplayName();
				} else {
					try {
						$share = $this->shareMapper->findByPollAndUser($poll->getId(), $logItem->getUserId());
						$displayName = $share->getUserObject()->getDisplayName();
					} catch (\Exception $e) {
						$displayName = $logItem->getUserId();
					}
				}

				$emailTemplate->addBodyText($this->getLogString($logItem, $displayName));
			}

			$logItem->setProcessed(time());
			$this->logMapper->update($logItem);
		}

		$emailTemplate->addBodyButton(
			htmlspecialchars($this->trans->t('Go to poll')),
			$url,
			/** @scrutinizer ignore-type */ false
		);
		$emailTemplate->addFooter($this->trans->t('This email is sent to you, because you subscribed to notifications of this poll. To opt out, visit the poll and remove your subscription.'));

		return $emailTemplate;
	}

	private function generateInvitation(UserGroupClass $recipient, Poll $poll, string $url): IEMailTemplate {
		$owner = $poll->getOwnerUserObject();
		$this->trans = $this->transFactory->get('polls', $recipient->getLanguage() ? $recipient->getLanguage() : $owner->getLanguage());

		$emailTemplate = $this->mailer->createEMailTemplate('polls.Invitation', [
			'owner' => $owner->getDisplayName(),
			'title' => $poll->getTitle(),
			'link' => $url
		]);

		$emailTemplate->setSubject($this->trans->t('Poll invitation "%s"', $poll->getTitle()));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->trans->t('Poll invitation "%s"', $poll->getTitle()), false);
		$emailTemplate->addBodyText(str_replace(
				['{owner}', '{title}'],
				[$owner->getDisplayName(), $poll->getTitle()],
				$this->trans->t('{owner} invited you to take part in the poll "{title}"')
			));
		$emailTemplate->addBodyText($poll->getDescription());
		$emailTemplate->addBodyButton(
				htmlspecialchars($this->trans->t('Go to poll')),
				$url
			);
		$emailTemplate->addBodyText($this->trans->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar: '));
		$emailTemplate->addBodyText($url);
		$emailTemplate->addBodyText($this->trans->t('Do not share this link with other people, because it is connected to your votes.'));
		$emailTemplate->addFooter($this->trans->t('This email is sent to you, because you are invited to vote in this poll by the poll owner. At least your name or your email address is recorded in this poll. If you want to get removed from this poll, contact the site administrator or the initiator of this poll, where the mail is sent from.'));

		return $emailTemplate;
	}
}
