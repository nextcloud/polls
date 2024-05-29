<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\Polls\Model\Mail;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\Share;

class InvitationMail extends MailBase {
	protected const TEMPLATE_CLASS = AppConstants::APP_ID . '.Invitation';

	public function __construct(
		protected string $recipientId,
		protected Share $share,
	) {
		parent::__construct($recipientId, $share->getPollId());
	}

	protected function getSubject(): string {
		return $this->l10n->t('Poll invitation "%s"', $this->poll->getTitle());
	}

	protected function getFooter(): string {
		return $this->l10n->t('This email is sent to you, because you are invited to vote in this poll by the poll owner. At least your name or your email address is recorded in this poll. If you want to get removed from this poll, contact the site administrator or the initiator of this poll, where the mail is sent from.');
	}

	protected function buildBody(): void {
		if ($this->share->getType() === Share::TYPE_GROUP) {
			$this->emailTemplate->addBodyText(str_replace(
				['{owner}', '{title}', '{group_name}'],
				[$this->owner->getDisplayName(), $this->poll->getTitle(), $this->share->getDisplayName()],
				$this->l10n->t('{owner} invited you to take part in the poll "{title}" as a member of the group {group_name}')
			));
		} else {
			$this->emailTemplate->addBodyText(str_replace(
				['{owner}', '{title}'],
				[$this->owner->getDisplayName(), $this->poll->getTitle()],
				$this->l10n->t('{owner} invited you to take part in the poll "{title}"')
			));
		}

		$this->emailTemplate->addBodyText($this->getRichDescription(), $this->poll->getDescription());

		$this->addButtonToPoll();

		$this->emailTemplate->addBodyText($this->l10n->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar:'));
		$this->emailTemplate->addBodyText($this->url);
		$this->emailTemplate->addBodyText($this->l10n->t('Do not share this link with other people, because it is connected to your votes.'));
	}
}
