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
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\Log;
use OCA\Polls\Exceptions\InvalidEmailAddress;
use OCA\Polls\Model\UserGroup\User;
use OCA\Polls\Model\Mail\InvitationMail;
use OCA\Polls\Model\Mail\ReminderMail;
use OCA\Polls\Model\Mail\NotificationMail;

class MailService {
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

	/** @var Log[] **/
	private $logs;

	/** @var Poll **/
	private $poll;

	public function __construct(
		string $AppName,
		LoggerInterface $logger,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IConfig $config,
		IURLGenerator $urlGenerator,
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
		$this->optionMapper = $optionMapper;
		$this->pollMapper = $pollMapper;
		$this->shareMapper = $shareMapper;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->poll = new Poll;
		$this->logs = [];
	}

	public function resolveEmailAddress(int $pollId, string $userId): string {
		if ($this->userManager->get($userId) instanceof IUser) {
			return $this->config->getUserValue($userId, 'settings', 'email');
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
		return '';
	}

	public function resendInvitation(string $token): Share {
		$this->sendInvitation($token);
		return $this->shareMapper->findByToken($token);
	}

	public function sendInvitation(string $token): array {
		$share = $this->shareMapper->findByToken($token);
		$sentMails = [];
		$abortedMails = [];

		foreach ($share->getUserObject()->getMembers() as $recipient) {
			$invitation = new InvitationMail($recipient->getId(), $share);

			try {
				$invitation->send();
				$sentMails[] = $recipient;
			} catch (InvalidEmailAddress $e) {
				$abortedMails[] = $recipient;
				$this->logger->warning('Invalid or no email address for invitation: ' . json_encode($recipient));
			} catch (\Exception $e) {
				$abortedMails[] = $recipient;
				$this->logger->error('Error sending Invitation to ' . json_encode($recipient));
			}
		}

		$share->setInvitationSent(time());
		$this->shareMapper->update($share);
		return ['sentMails' => $sentMails, 'abortedMails' => $abortedMails];
	}

	public function sendAutoReminder(): void {
		$polls = $this->pollMapper->findAutoReminderPolls();
		$time = time();
		$remindPolls = [];

		foreach ($polls as $poll) {
			if ($poll->getExpire()) {
				$deadline = $poll->getExpire();
				$reminderReason = ReminderMail::REASON_EXPIRATION;
			} elseif ($poll->getType() === Poll::TYPE_DATE) {
				// use first date option as reminder deadline
				$options = $this->optionMapper->findByPoll($poll->getId());
				$deadline = $options[0]->getTimestamp();
				$reminderReason = ReminderMail::REASON_OPTION;
			} else {
				// Textpolls without expirations are not processed
				continue;
			}

			if ($deadline - $poll->getCreated() > ReminderMail::FIVE_DAYS
				&& $deadline - $time < ReminderMail::TWO_DAYS
				&& $deadline > $time) {
				$timeToDeadline = ReminderMail::TWO_DAYS;
			} elseif ($deadline - $poll->getCreated() > ReminderMail::TWO_DAYS
				&& $deadline - $time < ReminderMail::ONE_AND_HALF_DAY
				&& $deadline > $time) {
				$timeToDeadline = ReminderMail::ONE_AND_HALF_DAY;
			} else {
				continue;
			}

			$shares = $this->shareMapper->findByPollUnreminded($poll->getId());
			foreach ($shares as $share) {
				if (in_array($share->getType(), [Share::TYPE_CIRCLE, Share::TYPE_CONTACTGROUP])) {
					continue;
				}

				foreach ($share->getUserObject()->getMembers() as $recipient) {
					$reminder = new ReminderMail(
						$recipient->getId(),
						$poll->getId(),
						$deadline,
						$timeToDeadline
					);

					try {
						$reminder->send();
					} catch (InvalidEmailAddress $e) {
						$this->logger->warning('Invalid or no email address for reminder: ' . json_encode($share));
					} catch (\Exception $e) {
						$this->logger->error('Error sending Reminder to ' . json_encode($share));
					}
				}

				$share->setReminderSent(time());
				$this->shareMapper->update($share);
			}
		}
	}

	public function sendNotifications(): void {
		$subscriptions = [];
		$this->logs = $this->logMapper->findUnprocessed();

		// Extract a unique array of pollIds from $this->logs
		// TODO: can we achieve this a little more elegant?
		$pollIds = array_values(array_unique(array_column(json_decode(json_encode($this->logs)), 'pollId')));

		// collect subscriptions for the polls to notify
		foreach ($pollIds as $pollId) {
			$subscriptions = array_merge($subscriptions, $this->subscriptionMapper->findAllByPoll($pollId));
		}

		foreach ($subscriptions as $subscription) {
			try {
				$subscription->setNotifyLogs($this->logs);

				$notication = new NotificationMail($subscription);
				$notication->send();
			} catch (InvalidEmailAddress $e) {
				$this->logger->warning('Invalid or no email address for notification: ' . json_encode($subscription));
			} catch (\Exception $e) {
				$this->logger->error('Error sending notification to ' . json_encode($subscription));
				continue;
			}
		}

		foreach ($this->logs as $logItem) {
			$logItem->setProcessed(time());
			$this->logMapper->update($logItem);
		}
	}
}
