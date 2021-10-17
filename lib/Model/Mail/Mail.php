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

use OCA\Polls\AppInfo\Application;
use OCA\Polls\Model\UserGroupClass;
use OCA\Polls\Model\User;
use OCA\Polls\Db\Poll;
use OCP\IL10N;
use OCP\App\IAppManager;
use OCP\L10N\IFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;


class Mail{
	private const TEMPLATE_CLASS = 'polls.Mail';

	/** @var UserGroupClass */
	protected $recipient;

	/** @var Poll */
	protected $poll;

	/** @var string */
	protected $url;

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
		UserGroupClass $recipient,
		Poll $poll,
		string $url
	) {
		$this->recipient = $recipient;
		$this->poll = $poll;
		$this->url = $url;
		$this->mailer = self::getContainer()->query(IMailer::class);
		$this->transFactory = self::getContainer()->query(IFactory::class);
		$this->owner = $poll->getOwnerUserObject();
		$this->trans = $this->transFactory->get(
			'polls',
			$this->recipient->getLanguage()
				? $this->recipient->getLanguage()
				: $this->owner->getLanguage()
		);

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
		if (!$this->recipient->getEmailAddress()
			|| !filter_var($this->recipient->getEmailAddress(), FILTER_VALIDATE_EMAIL)) {
			throw new \Exception('Invalid email address (' . $this->recipient->getEmailAddress() . ')');
		}

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

	protected static function getContainer() {
		$app = \OC::$server->query(Application::class);
		return $app->getContainer();
	}

}
