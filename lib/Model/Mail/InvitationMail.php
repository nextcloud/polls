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

use OCA\Polls\Db\Share;

class InvitationMail extends MailBase {
	private const TEMPLATE_CLASS = 'polls.Invitation';

	/** @var Share */
	protected $share;

	public function __construct(
		string $recipientId,
		Share $share
	) {
		parent::__construct($recipientId, $share->getPollId());
		$this->share = $share;
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

		$this->emailTemplate->addBodyText($this->getRichDescription(), $this->getRichDescription());

		if ($this->getButtonText() && $this->url) {
			$this->emailTemplate->addBodyButton($this->getButtonText(), $this->url);
		}

		$this->emailTemplate->addBodyText($this->l10n->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar:'));
		$this->emailTemplate->addBodyText($this->url);
		$this->emailTemplate->addBodyText($this->l10n->t('Do not share this link with other people, because it is connected to your votes.'));
	}
}
