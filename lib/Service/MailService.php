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
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\Log;
use OCA\Polls\Model\UserGroupClass;
use OCA\Polls\Model\User;
use OCA\Polls\Model\Mail\InvitationMail;
use OCA\Polls\Model\Mail\ReminderMail;
use League\CommonMark\CommonMarkConverter;

class MailService {
	private const FIVE_DAYS=432000;
	private const FOUR_DAYS=345600;
	private const THREE_DAYS=259200;
	private const TWO_DAYS=172800;
	private const ONE_AND_HALF_DAYS=129600;

	/** @var LoggerInterface */
	private $logger;

	/** @var string */
	private $appName;

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

	/** @var OptionMapper */
	private $optionMapper;

	/** @var LogMapper */
	private $logMapper;

	public function __construct(
		string $AppName,
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
		OptionMapper $optionMapper,
		PollMapper $pollMapper,
		LogMapper $logMapper
	) {
		$this->appName = $AppName;
		$this->logger = $logger;
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->urlGenerator = $urlGenerator;
		$this->logMapper = $logMapper;
		$this->mailer = $mailer;
		$this->optionMapper = $optionMapper;
		$this->pollMapper = $pollMapper;
		$this->shareMapper = $shareMapper;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->trans = $trans;
		$this->transFactory = $transFactory;
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

		$invitation = new InvitationMail(
			$share->getUserObject(),
			$this->pollMapper->find($share->getPollId()),
			$share->getURL()
		);

		$invitation->send();

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

			$invitation = new InvitationMail(
				$recipient,
				$poll,
				$share->getURL()
			);

			try {
				$invitation->send();

				$share->setInvitationSent(time());
				$this->shareMapper->update($share);

				$sentMails[] = $recipient;
			} catch (\Exception $e) {
				$abortedMails[] = $recipient;
				$this->logger->error('Error sending Mail to ' . json_encode($recipient));
			}
		}
		return ['sentMails' => $sentMails, 'abortedMails' => $abortedMails];
	}

	public function sendAutoReminder(): void {
		$polls = $this->pollMapper->findAutoReminderPolls();
		$time = time();
		$remindPolls = [];
		foreach ($polls as $poll) {
			if ($poll->getExpire()) {
				// If expiry is set, check reminder only depending on
				// the expiry date
				if ($poll->getExpire() - $poll->getCreated() > self::FIVE_DAYS
					&& $poll->getExpire() - $time < self::TWO_DAYS
					&& $poll->getExpire() > $time) {
					// If the span between poll creation and expiry date is
					// greater then 5 days, remind 48 hours before the
					// expiration date
					$this->remindShares($poll, ReminderMail::REASON_EXPIRATION, $poll->getExpire(), self::TWO_DAYS);
					continue;
				}

				if ($poll->getExpire() - $poll->getCreated() > self::TWO_DAYS
					&& $poll->getExpire() - $time < self::ONE_AND_HALF_DAYS
					&& $poll->getExpire() > $time) {
					// If the span between poll creation and expiry date is
					// greater then 2 days, remind 36 hours before the
					// expiration date
					$this->remindShares($poll, ReminderMail::REASON_EXPIRATION, $poll->getExpire(), self::ONE_AND_HALF_DAYS);
				}
				continue;
			}

			if ($poll->getType() === Poll::TYPE_DATE) {
				$options = $this->optionMapper->findByPoll($poll->getId());
				$checkOption = $options[0]->getTimestamp();
				// If expiry is not set and poll is a date poll, check reminder
				// depending on the first option
				if ($checkOption - $poll->getCreated() > self::FIVE_DAYS
					&& $checkOption - $time < self::TWO_DAYS
					&& $checkOption > $time) {
					// If the span between poll creation and first option is
					// greater then 5 days, remind 48 hours before the
					// first option
					$this->remindShares($poll, ReminderMail::REASON_OPTION, $checkOption, self::TWO_DAYS);
					continue;
				}

				if ($checkOption - $poll->getCreated() > self::TWO_DAYS
					&& $checkOption - $time < self::ONE_AND_HALF_DAYS
					&& $checkOption > $time) {
					// If the span between poll creation and first option is
					// greater then 2 days, remind 36 hours before the
					// first option
					$this->remindShares($poll, ReminderMail::REASON_OPTION, $checkOption, self::ONE_AND_HALF_DAYS);
				}
				continue;
			}
		}

	}

	private function remindShares(Poll $poll, string $reminderReason, int $deadline, int $period):void {
		$shares = $this->shareMapper->findByPollUnreminded($poll->getId());
		foreach ($shares as $share) {
			foreach ($share->getMembers() as $recipient) {
				$invitation = new ReminderMail($recipient, $poll, $share->getURL(), $reminderReason, $deadline, $period);
				try {
					$invitation->send();
				} catch (\Exception $e) {
					// catch silently
				}

			}

			$share->setReminderSent(time());
			$this->shareMapper->update($share);
		}
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
				$this->logger->error('Error sending Mail to ' . json_encode($recipient));
			}
		}
	}

	private function getLogString(Log $logItem, string $displayName): string {
		$logStrings = [
			Log::MSG_ID_SETVOTE => $this->trans->t('%s voted.', [$displayName]),
			Log::MSG_ID_UPDATEPOLL => $this->trans->t('Updated poll configuration. Please check your votes.'),
			Log::MSG_ID_DELETEPOLL => $this->trans->t('The poll got deleted.'),
			Log::MSG_ID_RESTOREPOLL => $this->trans->t('The poll got restored.'),
			Log::MSG_ID_EXPIREPOLL => $this->trans->t('The poll got closed.'),
			Log::MSG_ID_ADDOPTION => $this->trans->t('A vote option was added.'),
			Log::MSG_ID_UPDATEOPTION => $this->trans->t('A vote option changed.'),
			Log::MSG_ID_CONFIRMOPTION => $this->trans->t('A vote option got confirmed.'),
			Log::MSG_ID_DELETEOPTION => $this->trans->t('A vote option was removed.'),
			Log::MSG_ID_OWNERCHANGE => $this->trans->t('The poll owner changed.'),
			Log::MSG_ID_ADDPOLL => $this->trans->t('%s created the poll.', [$displayName]),
		];

		return $logStrings[$logItem->getMessageId()] ?? $logItem->getMessageId() . " (" . $displayName . ")";
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
			if (intval($logItem->getPollId()) === $poll->getId()) {
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

				$emailTemplate->addBodyListItem($this->getLogString($logItem, $displayName));
			}

			$logItem->setProcessed(time());
			$this->logMapper->update($logItem);
		}

		$emailTemplate->addBodyButton(htmlspecialchars($this->trans->t('Go to poll')), $url, '');
		$emailTemplate->addFooter($this->trans->t('This email is sent to you, because you subscribed to notifications of this poll. To opt out, visit the poll and remove your subscription.'));

		return $emailTemplate;
	}
}
