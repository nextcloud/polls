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

use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\Log;
use OCA\Polls\Exceptions\InvalidEmailAddress;
use OCA\Polls\Exceptions\NoDeadLineException;
use OCA\Polls\Model\UserGroup\User;
use OCA\Polls\Model\Mail\InvitationMail;
use OCA\Polls\Model\Mail\ReminderMail;
use OCA\Polls\Model\Mail\NotificationMail;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class MailService {
	/** @var LoggerInterface */
	private $logger;
	
	/** @var LogMapper */
	private $logMapper;
	
	/** @var Log[] **/
	private $logs;
	
	/** @var IUserManager */
	private $userManager;

	/** @var OptionMapper */
	private $optionMapper;
	
	/** @var PollMapper */
	private $pollMapper;
	
	/** @var SubscriptionMapper */
	private $subscriptionMapper;

	/** @var ShareMapper */
	private $shareMapper;
	
	public function __construct(
		IUserManager $userManager,
		LoggerInterface $logger,
		LogMapper $logMapper,
		OptionMapper $optionMapper,
		PollMapper $pollMapper,
		ShareMapper $shareMapper,
		SubscriptionMapper $subscriptionMapper
	) {
		$this->logger = $logger;
		$this->logMapper = $logMapper;
		$this->optionMapper = $optionMapper;
		$this->pollMapper = $pollMapper;
		$this->shareMapper = $shareMapper;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->userManager = $userManager;
		$this->logs = [];
	}

	public function resolveEmailAddress(int $pollId, string $userId): string {
		if ($this->userManager->get($userId) instanceof IUser) {
			$user = new User($userId);
			return $user->getEmailAddressMasked();
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
		
		foreach ($polls as $poll) {
			try {
				$this->processSharesForAutoReminder($poll);
			} catch (NoDeadLineException $e) {
				continue;
			}
		}
	}

	private function processSharesForAutoReminder(Poll $poll) {
		$shares = $this->shareMapper->findByPollUnreminded($poll->getId());
		foreach ($shares as $share) {
			if (in_array($share->getType(), [Share::TYPE_CIRCLE, Share::TYPE_CONTACTGROUP])) {
				continue;
			}

			$this->sendAutoReminderToRecipients($share, $poll);
			$share->setReminderSent(time());
			$this->shareMapper->update($share);
		}
	}

	private function sendAutoReminderToRecipients(Share $share, Poll $poll) {
		foreach ($share->getUserObject()->getMembers() as $recipient) {
			$reminder = new ReminderMail(
				$recipient->getId(),
				$poll->getId()
			);

			try {
				$reminder->send();
			} catch (InvalidEmailAddress $e) {
				$this->logger->warning('Invalid or no email address for reminder: ' . json_encode($share));
			} catch (\Exception $e) {
				$this->logger->error('Error sending Reminder to ' . json_encode($share));
			}
		}
	}
}
