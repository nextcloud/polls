<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Model\Mail;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\Poll;

class ConfirmationMail extends MailBase {
	protected const TEMPLATE_CLASS = AppConstants::APP_ID . '.Confirmation';

	/** @var Option[] */
	protected array $confirmedOptions;

	public function __construct(
		string $recipientId,
		int $pollId,
	) {
		parent::__construct($recipientId, $pollId);
		$this->confirmedOptions = $this->optionMapper->findConfirmed($pollId);
	}

	protected function getSubject(): string {
		return $this->l10n->t('Poll "%s" - Confirmation', $this->poll->getTitle());
	}

	protected function getFooter(): string {
		return $this->l10n->t('This email is sent to you to inform you about the result of a poll you participated in. At least your name or your email address was recorded in this poll. If you want to be removed from this poll, contact the site administrator or the poll initiator, where the mail is sent from.');
	}

	protected function buildBody(): void {
		$this->emailTemplate->addBodyText(str_replace(
			['{owner}', '{title}'],
			[$this->owner->getDisplayName(), $this->poll->getTitle()],
			$this->l10n->t('{owner} wants to inform you about the final result of the poll "{title}"')
		));

		$this->emailTemplate->addBodyText(
			$this->l10n->n('Confirmed option:', 'Confirmed options:', count($this->confirmedOptions))
		);

		foreach ($this->confirmedOptions as $option) {
			if ($this->poll->getType() === Poll::TYPE_DATE) {
				$this->emailTemplate->addBodyListItem($option->getDateStringLocalized($this->recipient->getTimeZone(), $this->l10n));
			} else {
				$this->emailTemplate->addBodyListItem($option->getPollOptionText());
			}
		}

		if ($this->poll->getType() === Poll::TYPE_DATE) {
			$this->emailTemplate->addBodyText(
				$this->l10n->t('The used time zone is "%s", based on the detected time zone at your registration time. To view the times in your current time zone, enter the poll by clicking the button below.', $this->recipient->getTimeZoneName())
			);
		}

		if ($this->poll->getDescription()) {
			$this->emailTemplate->addBodyText($this->getRichDescription(), $this->poll->getDescription());
		}

		$this->addButtonToPoll();

		$this->emailTemplate->addBodyText($this->l10n->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar:'));
		$this->emailTemplate->addBodyText($this->url);
		$this->emailTemplate->addBodyText($this->l10n->t('Do not share this link with other people, because it is connected to your votes.'));
	}
}
