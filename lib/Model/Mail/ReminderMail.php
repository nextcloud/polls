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

use OCA\Polls\Db\Poll;
use OCA\Polls\Model\UserGroupClass;
use OCA\Polls\Model\User;
use OCA\Polls\Model\Mail\IMail;
use OCP\IL10N;
use OCP\App\IAppManager;
use OCP\L10N\IFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;

class ReminderMail extends Mail implements IMail {
	private const TEMPLATE_CLASS = 'polls.Reminder';
	public const REASON_EXPIRATION = 'expiry';
	public const REASON_OPTION = 'option';

	/** @var string */
	protected $reason;

	/** @var int */
	protected $deadline;

	/** @var int */
	protected $remainingPeriodLess;

	public function __construct(
		UserGroupClass $recipient,
		Poll $poll,
		string $url,
		string $reason,
		int $deadline,
		int $remainingPeriodLess
	) {
		parent::__construct($recipient, $poll, $url);
		$this->reason = $reason;
		$this->deadline = $deadline;
		$this->remainingPeriodLess = $remainingPeriodLess;
		$this->buildEmailTemplate();
	}

	public function buildEmailTemplate() : void {
		$this->emailTemplate->setSubject($this->trans->t('Reminder for poll "%s"', $this->poll->getTitle()));
		$this->emailTemplate->addHeader();
		$this->emailTemplate->addHeading($this->trans->t('Reminder for poll "%s"', $this->poll->getTitle()), false);

		$this->emailTemplate->addBodyText(
			$this->trans->t('This is just a reminder, to make sure you do not miss the deadline for this poll.')
		);

		if ($this->reason === self::REASON_OPTION) {
			$this->emailTemplate->addBodyText(str_replace(
				['{leftPeriod}','{dateTime}'],
				[($this->remainingPeriodLess / 3600), $this->deadline],
				$this->trans->t('The first poll option is away less than {leftPeriod} hours ({dateTime}).')
			));
		}

		if ($this->reason === self::REASON_EXPIRATION) {
			$this->emailTemplate->addBodyText(str_replace(
				['{leftPeriod}','{dateTime}'],
				[($this->remainingPeriodLess / 3600), $this->deadline],
				$this->trans->t('The poll is about to expire in less than {leftPeriod} hours ({dateTime}).')
			));
		}

		$this->emailTemplate->addBodyButton(
				$this->trans->t('Go to poll'),
				$this->url
			);
		$this->emailTemplate->addBodyText($this->trans->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar:'));
		$this->emailTemplate->addBodyText($this->url);
		$this->emailTemplate->addBodyText($this->trans->t('Do not share this link with other people, because it is connected to your votes.'));
		$this->emailTemplate->addFooter($this->trans->t('This email is sent to you, because you are invited to vote in this poll by the poll owner. At least your name or your email address is recorded in this poll. If you want to get removed from this poll, contact the site administrator or the initiator of this poll, where the mail is sent from.'));
	}
}
