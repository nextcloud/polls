<?php

declare(strict_types=1);
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

use OCA\Polls\Db\Log;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Exceptions\InvalidEmailAddress;
use OCA\Polls\Exceptions\NoDeadLineException;
use OCA\Polls\Model\Mail\ConfirmationMail;
use OCA\Polls\Model\Mail\InvitationMail;
use OCA\Polls\Model\Mail\NotificationMail;
use OCA\Polls\Model\Mail\ReminderMail;
use OCA\Polls\Model\SentResult;
use OCA\Polls\Model\UserBase;
use Psr\Log\LoggerInterface;

class MailService {
	/** @var Log[] **/
	private array $logs;

	public function __construct(
		private LoggerInterface $logger,
		private LogMapper $logMapper,
		private PollMapper $pollMapper,
		private ShareMapper $shareMapper,
		private SubscriptionMapper $subscriptionMapper,
		private UserMapper $userMapper,
	) {
		$this->logs = [];
	}

	public function resolveEmailAddress(int $pollId, string $userId): string {
		return $this->userMapper->getParticipant($userId, $pollId)->getEmailAddress();
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
			$subscription->setNotifyLogs($this->logs);
			$notication = new NotificationMail($subscription);

			try {
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

	public function sendInvitation(
		Share $share,
		SentResult &$sentResult = null,
		string $token = null,
	): SentResult|null {
		if ($token) {
			$share = $this->shareMapper->findByToken($token);
		}

		foreach ($this->userMapper->getUserFromShare($share)->getMembers() as $recipient) {
			$invitation = new InvitationMail($recipient->getId(), $share);

			try {
				$invitation->send();
				if ($sentResult) {
					$sentResult->AddSentMail($recipient);
				}
			} catch (InvalidEmailAddress $e) {
				if ($sentResult) {
					$sentResult->AddAbortedMail($recipient, SentResult::INVALID_EMAIL_ADDRESS);
				}
				$this->logger->warning('Invalid or no email address for invitation: ' . json_encode($recipient));
			} catch (\Exception $e) {
				if ($sentResult) {
					$sentResult->AddAbortedMail($recipient);
				}
				$this->logger->error('Error sending Invitation to ' . json_encode($recipient));
			}
		}

		$share->setInvitationSent(time());
		$this->shareMapper->update($share);

		return $sentResult;
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

	/**
	 * Send a confirmation mail for the poll to all participants
	 */
	public function sendConfirmations(int $pollId): SentResult {
		$sentResult = new SentResult();
		/** @var UserBase[] */
		$participants = $this->userMapper->getParticipants($pollId);

		foreach ($participants as $participant) {
			try {
				$this->sendConfirmationMail($participant, $pollId);
				$sentResult->AddSentMail($participant);
			} catch (InvalidEmailAddress $e) {
				$this->logger->warning('Invalid or no email address for confirmation: ' . json_encode($participant));
				$sentResult->AddAbortedMail($participant, SentResult::INVALID_EMAIL_ADDRESS);
			} catch (\Exception $e) {
				$this->logger->error('Error sending confirmation to ' . json_encode($participant));
				$sentResult->AddAbortedMail($participant);
			}
		}

		return $sentResult;
	}

	private function processSharesForAutoReminder(Poll $poll): void {
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

	private function sendConfirmationMail(UserBase $participant, int $pollId): void {
		$confirmation = new ConfirmationMail($participant->getId(), $pollId);
		$confirmation->send();
	}

	private function sendAutoReminderToRecipients(Share $share, Poll $poll): void {
		foreach ($this->userMapper->getUserFromShare($share)->getMembers() as $recipient) {
			$reminder = new ReminderMail(
				$recipient->getId(),
				$poll->getId()
			);

			try {
				$reminder->send();
			} catch (InvalidEmailAddress $e) {
				$this->logger->warning('Invalid or no email address for reminder: ' . json_encode($share));
			} catch (\Exception $e) {
				$this->logger->error('Error sending reminder to ' . json_encode($share));
			}
		}
	}
}
