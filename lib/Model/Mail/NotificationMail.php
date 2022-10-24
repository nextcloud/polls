<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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


namespace OCA\Polls\Model\Mail;

use OCA\Polls\Db\Log;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\Subscription;
use OCA\Polls\Event\CommentEvent;
use OCA\Polls\Event\PollEvent;
use OCA\Polls\Event\OptionEvent;
use OCA\Polls\Event\VoteEvent;

class NotificationMail extends MailBase {
	private const TEMPLATE_CLASS = 'polls.Notification';

	/** @var Log[] */
	protected $log;

	/** @var Subscription **/
	protected $subscription;

	public function __construct(
		Subscription $subscription
	) {
		parent::__construct($subscription->getUserId(), $subscription->getPollId());
		$this->subscription = $subscription;
	}

	protected function getSubject(): string {
		return $this->l10n->t('Polls App - New Activity');
	}

	protected function getFooter(): string {
		return $this->l10n->t('This email is sent to you, because you subscribed to notifications of this poll. To opt out, visit the poll and remove your subscription.');
	}

	protected function buildBody(): void {
		$this->emailTemplate->addBodyText(str_replace(
			['{title}'],
			[$this->poll->getTitle()],
			$this->l10n->t('"{title}" has recent activity:')
		));

		foreach ($this->subscription->getNotifyLogs() as $logItem) {
			$displayName = $this->evaluateDisplayName($logItem);
			$this->emailTemplate->addBodyListItem($this->getComposedLogString($logItem, $displayName));
		}

		$this->addButtonToPoll();
	}

	private function evaluateDisplayName(Log $logItem) {
		if ($this->poll->getAnonymous() || $this->poll->getShowResults() !== Poll::SHOW_RESULTS_ALWAYS) {
			// hide actor's name if poll is anonymous or results are hidden
			return $this->l10n->t('A user');
		}

		return $this->getUser($logItem->getUserId())->getDisplayName();
	}

	private function getComposedLogString(Log $logItem, string $displayName): string {
		$logStrings = [
			Log::MSG_ID_SETVOTE => $this->l10n->t('%s has voted.', [$displayName]),
			Log::MSG_ID_UPDATEPOLL => $this->l10n->t('Updated poll configuration. Please check your votes.'),
			Log::MSG_ID_DELETEPOLL => $this->l10n->t('The poll has been deleted.'),
			Log::MSG_ID_RESTOREPOLL => $this->l10n->t('The poll has been restored.'),
			Log::MSG_ID_EXPIREPOLL => $this->l10n->t('The poll has been closed.'),
			Log::MSG_ID_ADDOPTION => $this->l10n->t('A voting option has been added.'),
			Log::MSG_ID_UPDATEOPTION => $this->l10n->t('A voting option has been changed.'),
			Log::MSG_ID_CONFIRMOPTION => $this->l10n->t('A voting option has been confirmed.'),
			Log::MSG_ID_DELETEOPTION => $this->l10n->t('A voting option has been removed.'),
			Log::MSG_ID_OWNERCHANGE => $this->l10n->t('The poll owner has been changed.'),
			Log::MSG_ID_ADDPOLL => $this->l10n->t('%s created the poll.', [$displayName]),
			PollEvent::ADD => $this->l10n->t('%s created the poll.', [$displayName]),
			PollEvent::UPDATE => $this->l10n->t('Updated poll configuration. Please check your votes.'),
			PollEvent::DELETE => $this->l10n->t('The poll has been deleted.'),
			PollEvent::RESTORE => $this->l10n->t('The poll has been restored.'),
			PollEvent::EXPIRE => $this->l10n->t('The poll has been closed.'),
			PollEvent::OWNER_CHANGE => $this->l10n->t('The poll owner has been changed.'),
			OptionEvent::ADD => $this->l10n->t('A voting option has been added.'),
			OptionEvent::UPDATE => $this->l10n->t('A voting option has been changed.'),
			OptionEvent::CONFIRM => $this->l10n->t('A voting option has been confirmed.'),
			OptionEvent::UNCONFIRM => $this->l10n->t('A voting option has been unconfirmed.'),
			OptionEvent::DELETE => $this->l10n->t('A voting option has been removed.'),
			CommentEvent::ADD => $this->l10n->t('%s has left a comment.', [$displayName]),
			VoteEvent::SET => $this->l10n->t('%s has voted.', [$displayName]),
		];

		return $logStrings[$logItem->getMessageId()] ?? $logItem->getMessageId() . ' (' . $displayName . ')';
	}
}
