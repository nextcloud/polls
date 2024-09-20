<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Model\Mail;

use DateTime;
use OCA\Polls\AppConstants;
use OCA\Polls\Db\Poll;

class ReminderMail extends MailBase {
	protected const TEMPLATE_CLASS = AppConstants::APP_ID . '.Reminder';
	public const REASON_EXPIRATION = 'expiry';
	public const REASON_OPTION = 'option';
	public const REASON_NONE = null;
	public const FIVE_DAYS = 432000;
	public const FOUR_DAYS = 345600;
	public const THREE_DAYS = 259200;
	public const TWO_DAYS = 172800;
	public const ONE_AND_HALF_DAY = 129600;

	protected int $deadline;
	protected int $timeToDeadline;

	public function __construct(
		protected string $recipientId,
		protected int $pollId
	) {
		parent::__construct($recipientId, $pollId);
		$this->deadline = $this->poll->getDeadline();
		$this->timeToDeadline = $this->poll->getTimeToDeadline();
	}

	protected function getSubject(): string {
		return $this->l10n->t('Reminder for poll "%s"', $this->poll->getTitle());
	}

	protected function getButtonText(): string {
		return $this->l10n->t('Check your votes');
	}

	protected function getFooter(): string {
		return $this->l10n->t('This email is sent to you, because you are invited to vote in this poll by the poll owner. At least your name or your email address is recorded in this poll. If you want to get removed from this poll, contact the site administrator or the initiator of this poll, where the mail is sent from.');
	}

	protected function buildBody(): void {
		$this->addBodyText();
		$this->addButtonToPoll();
		$this->emailTemplate->addBodyText($this->l10n->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar:'));
		$this->emailTemplate->addBodyText($this->url);
		$this->emailTemplate->addBodyText($this->l10n->t('Do not share this link with other people, because it is connected to your votes.'));
	}

	private function addBodyText(): void {
		$dtDeadline = new DateTime('now', $this->recipient->getTimeZone());
		$dtDeadline->setTimestamp($this->deadline);
		$deadlineText = (string)$this->l10n->l('datetime', $dtDeadline, ['width' => 'long']);

		if ($this->getReminderReason() === self::REASON_OPTION) {
			$this->emailTemplate->addBodyText(str_replace(
				['{leftPeriod}', '{dateTime}', '{timezone}'],
				[($this->timeToDeadline / 3600), $deadlineText, $this->recipient->getTimeZone()->getName()],
				$this->l10n->t('The first poll option is away less than {leftPeriod} hours ({dateTime}, {timezone}).')
			));
			return;
		}

		if ($this->getReminderReason() === self::REASON_EXPIRATION) {
			$this->emailTemplate->addBodyText(str_replace(
				['{leftPeriod}', '{dateTime}', '{timezone}'],
				[($this->timeToDeadline / 3600), $deadlineText, $this->recipient->getTimeZone()->getName()],
				$this->l10n->t('The poll is about to expire in less than {leftPeriod} hours ({dateTime}, {timezone}).')
			));
			return;
		}

		$this->emailTemplate->addBodyText(str_replace(
			['{owner}'],
			[$this->owner->getDisplayName()],
			$this->l10n->t('{owner} sends you this reminder to make sure, your votes are set.')
		));
	}

	private function getReminderReason() : string|null {
		if ($this->poll->getExpire()) {
			return self::REASON_EXPIRATION;
		} elseif ($this->poll->getType() === Poll::TYPE_DATE) {
			return self::REASON_OPTION;
		} else {
			return self::REASON_NONE;
		}
	}
}
