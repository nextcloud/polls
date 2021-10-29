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
use OCA\Polls\Db\Subscription;

class NotificationMail extends MailBase {
	private const TEMPLATE_CLASS = 'polls.Notification';

	/** @var Log[] */
	protected $log;

	/** @var Subscription **/
	protected $subscription;

	public function __construct(
		Subscription $subscription
	) {
		parent::__construct(
			$subscription->getUserId(),
			$subscription->getPollId(),
		);
		$this->subscription = $subscription;
		$this->buildEmailTemplate();
	}

	public function buildEmailTemplate() : void {
		$this->emailTemplate->setSubject($this->trans->t('Polls App - New Activity'));
		$this->emailTemplate->addHeader();
		$this->emailTemplate->addHeading($this->trans->t('Polls App - New Activity'), false);
		$this->emailTemplate->addBodyText(str_replace(
			['{title}'],
			[$this->poll->getTitle()],
			$this->trans->t('"{title}" had recent activity: ')
		));

		foreach ($this->subscription->getNotifyLogs() as $logItem) {
			if ($this->poll->getAnonymous() || $this->poll->getShowResults() !== "always") {
				// hide actor's name if poll is anonymous or results are hidden
				$displayName = $this->trans->t('A user');
			} else {
				$displayName = $this->getUser($logItem->getUserId())->getDisplayName();
			}

			$this->emailTemplate->addBodyListItem($this->getComposedLogString($logItem, $displayName));
		}

		$this->emailTemplate->addBodyButton(htmlspecialchars($this->trans->t('Go to poll')), $this->url, '');
		$this->emailTemplate->addFooter($this->trans->t('This email is sent to you, because you subscribed to notifications of this poll. To opt out, visit the poll and remove your subscription.'));
	}

	private function getComposedLogString(Log $logItem, string $displayName): string {
		$logStrings = [
			Log::MSG_ID_SETVOTE => $this->trans->t('%s has voted.', [$displayName]),
			Log::MSG_ID_UPDATEPOLL => $this->trans->t('Updated poll configuration. Please check your votes.'),
			Log::MSG_ID_DELETEPOLL => $this->trans->t('The poll has been deleted.'),
			Log::MSG_ID_RESTOREPOLL => $this->trans->t('The poll has been restored.'),
			Log::MSG_ID_EXPIREPOLL => $this->trans->t('The poll has been closed.'),
			Log::MSG_ID_ADDOPTION => $this->trans->t('A voting option has been added.'),
			Log::MSG_ID_UPDATEOPTION => $this->trans->t('A voting option has been changed.'),
			Log::MSG_ID_CONFIRMOPTION => $this->trans->t('A voting option has been confirmed.'),
			Log::MSG_ID_DELETEOPTION => $this->trans->t('A voting option has been removed.'),
			Log::MSG_ID_OWNERCHANGE => $this->trans->t('The poll owner has been changed.'),
			Log::MSG_ID_ADDPOLL => $this->trans->t('%s created the poll.', [$displayName]),
		];

		return $logStrings[$logItem->getMessageId()] ?? $logItem->getMessageId() . " (" . $displayName . ")";
	}
}
