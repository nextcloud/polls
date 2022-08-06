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

use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Helper\Container;

class ConfirmationMail extends MailBase {
	private const TEMPLATE_CLASS = 'polls.Confirmation';

	/** @var OptionMapper */
	protected $optionMapper;

	public function __construct(
		string $recipientId,
		int $pollId
	) {
		parent::__construct($recipientId, $pollId);
		$this->optionMapper = Container::queryClass(OptionMapper::class);
	}

	protected function getSubject(): string {
		return $this->l10n->t('Poll "%s" has been closed', $this->poll->getTitle());
	}

	protected function getFooter(): string {
		return $this->l10n->t('This email is sent to you, to inform you about the result of a poll, you participated in. At least your name or your email address is recorded in this poll. If you want to get removed from this poll, contact the site administrator or the initiator of this poll, where the mail is sent from.');
	}

	protected function buildBody(): void {
		$this->emailTemplate->addBodyText(str_replace(
			['{owner}', '{title}'],
			[$this->owner->getDisplayName(), $this->poll->getTitle()],
			$this->l10n->t('{owner} wants to inform you about the final result of the poll "{title}"')
		));

		$confirmedOptions = $this->optionMapper->findConfirmed($this->poll->getId());
		$countConfirmed = count($confirmedOptions);

		$this->emailTemplate->addBodyText(
			$this->l10n->n('Confirmed option:', 'Confirmed options:', $countConfirmed)
		);

		foreach ($confirmedOptions as $option) {
			$this->emailTemplate->addBodyListItem(
				$option->getPollOptionText()
			);
		}

		$this->emailTemplate->addBodyText($this->getRichDescription(), $this->poll->getDescription());
		$this->addButtonToPoll();

		$this->emailTemplate->addBodyText($this->l10n->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar:'));
		$this->emailTemplate->addBodyText($this->url);
		$this->emailTemplate->addBodyText($this->l10n->t('Do not share this link with other people, because it is connected to your votes.'));
	}
}
