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
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Model\UserGroupClass;
use OCA\Polls\Model\User;
use OCA\Polls\Model\Mail\IMail;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\App\IAppManager;
use OCP\L10N\IFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use League\CommonMark\CommonMarkConverter;

class InvitationMail extends Mail implements IMail {
	private const TEMPLATE_CLASS = 'polls.Notification';

	/** @var array */
	protected $logEntries;

	/** @var IUserManager **/
	protected $userManager;

	/** @var ShareMapper **/
	protected $shareMapper;

	public function __construct(
		UserGroupClass $recipient,
		Poll $poll,
		string $url,
		array $logEntries
	) {
		parent::__construct($recipient, $poll, $url);
		$this->userManager = self::getContainer()->query(IUserManager::class);
		$this->shareMapper = self::getContainer()->query(ShareMapper::class);
		$this->logEntries = $logEntries;
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

		foreach ($this->logEntries as $logItem) {
			$this->emailTemplate->addBodyListItem($logItem);
		}

		$this->emailTemplate->addBodyButton(htmlspecialchars($this->trans->t('Go to poll')), $url, '');
		$this->emailTemplate->addFooter($this->trans->t('This email is sent to you, because you subscribed to notifications of this poll. To opt out, visit the poll and remove your subscription.'));
	}
}
