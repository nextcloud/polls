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

use OCA\Polls\Db\Option;
use OCA\Polls\Db\Poll;

class ConfirmationMail extends MailBase {
	private const TEMPLATE_CLASS = 'polls.Confirmation';

	/** @var Option[] */
	protected array $confirmedOptions;

	public function __construct(
		string $recipientId,
		int $pollId
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

		// $this->emailTemplate->addBodyText(
		// 	$this->l10n->t('Used languageCode is %1$s, %2$s, %3$s', [$this->l10n->getLanguageCode(), $this->l10n->getLocaleCode(), $this->transFactory->localeExists($this->l10n->getLanguageCode())])
		// );

		if ($this->poll->getDescription()) {
			$this->emailTemplate->addBodyText($this->getRichDescription(), $this->poll->getDescription());
		}

		$this->addButtonToPoll();

		$this->emailTemplate->addBodyText($this->l10n->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar:'));
		$this->emailTemplate->addBodyText($this->url);
		$this->emailTemplate->addBodyText($this->l10n->t('Do not share this link with other people, because it is connected to your votes.'));
	}
}
