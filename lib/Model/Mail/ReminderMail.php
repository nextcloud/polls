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

use DateTime;
use OCA\Polls\Db\Poll;

class ReminderMail extends MailBase {
	private const TEMPLATE_CLASS = 'polls.Reminder';
	public const REASON_EXPIRATION = 'expiry';
	public const REASON_OPTION = 'option';
	public const REASON_NONE = null;
	public const FIVE_DAYS = 432000;
	public const FOUR_DAYS = 345600;
	public const THREE_DAYS = 259200;
	public const TWO_DAYS = 172800;
	public const ONE_AND_HALF_DAY = 129600;

	/** @var int */
	protected $deadline;

	/** @var int */
	protected $timeToDeadline;

	public function __construct(
		string $recipientId,
		int $pollId,
		int $deadline,
		int $timeToDeadline
	) {
		parent::__construct($recipientId, $pollId);
		$this->deadline = $deadline;
		$this->timeToDeadline = $timeToDeadline;
		$this->buildEmailTemplate();
	}

	public function buildEmailTemplate() : void {
		$dtDeadline = new DateTime('now', $this->recipient->getTimeZone());
		$dtDeadline->setTimestamp($this->deadline);
		$deadlineText = (string) $this->trans->l('datetime', $dtDeadline, ['width' => 'long']);

		$this->emailTemplate->setSubject($this->trans->t('Reminder for poll "%s"', $this->poll->getTitle()));
		$this->emailTemplate->addHeader();
		$this->emailTemplate->addHeading($this->trans->t('Reminder for poll "%s"', $this->poll->getTitle()), false);

		$reminderText = str_replace(
			['{owner}'],
			[$this->owner->getDisplayName()],
			$this->trans->t('{owner} sends you this reminder to make sure, your votes are set.')
		);

		if ($this->getReminderReason() === self::REASON_OPTION) {
			$reminderText = str_replace(
				['{leftPeriod}', '{dateTime}', '{timezone}'],
				[($this->timeToDeadline / 3600), $deadlineText, $this->recipient->getTimeZone()->getName()],
				$this->trans->t('The first poll option is away less than {leftPeriod} hours ({dateTime}, {timezone}).')
			);
		}

		if ($this->getReminderReason() === self::REASON_EXPIRATION) {
			$reminderText = str_replace(
				['{leftPeriod}', '{dateTime}', '{timezone}'],
				[($this->timeToDeadline / 3600), $deadlineText, $this->recipient->getTimeZone()->getName()],
				$this->trans->t('The poll is about to expire in less than {leftPeriod} hours ({dateTime}, {timezone}).')
			);
		}

		$this->emailTemplate->addBodyText($reminderText);
		$this->emailTemplate->addBodyButton(
				$this->trans->t('Check your votes'),
				$this->url
			);
		$this->emailTemplate->addBodyText($this->trans->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar:'));
		$this->emailTemplate->addBodyText($this->url);
		$this->emailTemplate->addBodyText($this->trans->t('Do not share this link with other people, because it is connected to your votes.'));
		$this->emailTemplate->addFooter($this->trans->t('This email is sent to you, because you are invited to vote in this poll by the poll owner. At least your name or your email address is recorded in this poll. If you want to get removed from this poll, contact the site administrator or the initiator of this poll, where the mail is sent from.'));
	}

	public function getReminderReason() : ?string {
		if ($this->poll->getExpire()) {
			return self::REASON_EXPIRATION;
		} elseif ($this->poll->getType() === Poll::TYPE_DATE) {
			return self::REASON_OPTION;
		} else {
			return self::REASON_NONE;
		}
	}
}
