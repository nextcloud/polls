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

use OCA\Polls\Model\UserGroup\UserBase;
use OCA\Polls\Model\UserGroup\User;
use OCA\Polls\Db\Poll;
use OCA\Polls\Helper\Container;
use OCP\IL10N;
use OCP\IUser;
use OCA\Polls\Exceptions\InvalidEmailAddress;
use OCP\L10N\IFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;

class MailBase {
	private const TEMPLATE_CLASS = 'polls.Mail';

	/** @var UserBase */
	protected $recipient;

	/** @var Poll */
	protected $poll;

	/** @var string|null */
	protected $url = null;

	/** @var string */
	protected $footer;

	/** @var IMailer */
	protected $mailer;

	/** @var IL10N */
	protected $trans;

	/** @var IFactory */
	protected $transFactory;

	/** @var IEMailTemplate */
	protected $emailTemplate;

	/** @var User */
	protected $owner;

	public function __construct(
		string $recipientId,
		int $pollId,
		string $url = null
	) {
		$this->poll = $this->getPoll($pollId);
		$this->recipient = $this->getUser($recipientId);
		$this->url = $url ?? $this->poll->getVoteUrl();

		$this->initializeClass();
	}

	protected function initializeClass(): void {
		$this->owner = $this->poll->getOwnerUserObject();

		if ($this->recipient->getIsNoUser()) {
			$this->url = $this->getShareURL();
		}

		$this->mailer = Container::queryClass(IMailer::class);
		$this->transFactory = Container::queryClass(IFactory::class);
		$this->trans = $this->transFactory->get(
			'polls',
			$this->recipient->getLanguage()
				? $this->recipient->getLanguage()
				: $this->owner->getLanguage()
		);

		$this->footer = $this->trans->t('This email is sent to you, because you subscribed to notifications of this poll. To opt out, visit the poll and remove your subscription.');
		$this->emailTemplate = $this->mailer->createEMailTemplate(
			self::TEMPLATE_CLASS, [
				'owner' => $this->owner->getDisplayName(),
				'title' => $this->poll->getTitle(),
				'link' => $this->url
			]
		);
	}

	public function getEmailTemplate() : IEMailTemplate {
		return $this->emailTemplate;
	}

	public function send(): void {
		$this->validateEmailAddress();

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$this->recipient->getEmailAddress() => $this->recipient->getDisplayName()]);
			$message->useTemplate($this->emailTemplate);
			$this->mailer->send($message);
		} catch (\Exception $e) {
			\OC::$server->getLogger()->error('Error sending Mail to ' . json_encode($this->recipient));
			\OC::$server->getLogger()->alert($e->getMessage());
			throw $e;
		}
	}

	protected function getUser(string $userId) : UserBase {
		if (\OC::$server->getUserManager()->get($userId) instanceof IUser) {
			// return User object
			return new User($userId);
		}
		// return UserBaseChild from share
		return Container::findShare($this->poll->getId(), $userId)->getUserObject();
	}

	protected function getShareURL() : string {
		return Container::findShare($this->poll->getId(), $this->recipient->getId())->getURL();
	}

	protected function getPoll(int $pollId) : Poll {
		return Container::queryPoll($pollId);
	}

	protected function validateEmailAddress(): void {
		if (!$this->recipient->getEmailAddress()
			|| !filter_var($this->recipient->getEmailAddress(), FILTER_VALIDATE_EMAIL)) {
			throw new InvalidEmailAddress('Invalid email address (' . $this->recipient->getEmailAddress() . ')');
		}
	}
}
