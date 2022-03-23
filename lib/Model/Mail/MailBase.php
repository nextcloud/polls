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
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Db\Poll;
use OCA\Polls\Helper\Container;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCA\Polls\Exceptions\InvalidEmailAddress;
use OCP\L10N\IFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use Psr\Log\LoggerInterface;

abstract class MailBase {
	private const TEMPLATE_CLASS = 'polls.Mail';

	/** @var UserBase */
	protected $recipient;

	/** @var LoggerInterface */
	protected $logger;

	/** @var Poll */
	protected $poll;

	/** @var string|null */
	protected $url = null;

	/** @var string */
	protected $footer;

	/** @var IMailer */
	protected $mailer;

	/** @var IL10N */
	protected $l10n;

	/** @var IFactory */
	protected $transFactory;

	/** @var IUserManager */
	private $userManager;

	/** @var IEmailTemplate */
	protected $emailTemplate;

	/** @var AppSettings */
	protected $appSettings;

	/** @var User */
	protected $owner;

	public function __construct(
		string $recipientId,
		int $pollId,
		string $url = null
	) {
		$this->userManager = Container::queryClass(IUserManager::class);
		$this->logger = Container::queryClass(LoggerInterface::class);
		$this->mailer = Container::queryClass(IMailer::class);
		$this->transFactory = Container::queryClass(IFactory::class);
		$this->appSettings = Container::queryClass(AppSettings::class);

		$this->poll = $this->getPoll($pollId);
		$this->recipient = $this->getUser($recipientId);
		$this->url = $url ?? $this->poll->getVoteUrl();

		$this->initializeClass();
	}

	public function send(): void {
		$this->validateEmailAddress();

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$this->recipient->getEmailAddress() => $this->recipient->getDisplayName()]);
			$message->useTemplate($this->getEmailTemplate());
			$this->mailer->send($message);
		} catch (\Exception $e) {
			$this->logger->error('Error sending Mail to ' . json_encode($this->recipient));
			$this->logger->alert($e->getMessage());
			throw $e;
		}
	}

	protected function initializeClass(): void {
		$this->owner = $this->poll->getOwnerUserObject();

		if ($this->recipient->getIsNoUser()) {
			$this->url = $this->getShareURL();
		}

		$this->l10n = $this->transFactory->get(
			'polls',
			$this->recipient->getLanguage()
				? $this->recipient->getLanguage()
				: $this->owner->getLanguage()
		);
	}

	private function getEmailTemplate() : IEMailTemplate {
		$this->emailTemplate = $this->mailer->createEMailTemplate(
			self::TEMPLATE_CLASS, [
				'owner' => $this->owner->getDisplayName(),
				'title' => $this->poll->getTitle(),
				'link' => $this->url
			]
		);

		$this->emailTemplate->setSubject($this->getSubject());

		// add heading
		$this->emailTemplate->addHeader();
		$this->emailTemplate->addHeading($this->getHeading(), false);

		$this->buildBody();

		// add footer
		$footerText = $this->getFooter();
		if ($this->appSettings->getLegalTermsInEmail()) {
			$footerText = $footerText . '<br>' . $this->getLegalLinks();
		}

		if ($this->appSettings->getDisclaimer()) {
			$footerText = $footerText . '<br>' . $this->getParsedMarkDown($this->appSettings->getDisclaimer());
		}

		$this->emailTemplate->addFooter($footerText);
		return $this->emailTemplate;
	}



	protected function getSubject(): string {
		return $this->l10n->t('Notification for poll "%s"', $this->poll->getTitle());
	}

	protected function getHeading(): string {
		return $this->getSubject();
	}

	protected function getButtonText(): string {
		return $this->l10n->t('Go to poll');
	}

	protected function getFooter(): string {
		return $this->l10n->t('This email is sent to you, because you subscribed to notifications of this poll. To opt out, visit the poll and remove your subscription.');
	}

	protected function buildBody(): void {
		$this->emailTemplate->addBodyText('Sorry. This eMail has no text and this should not happen.');
	}

	protected function getLegalLinks() {
		$legal = '';

		if ($this->appSettings->getUseImprintUrl()) {
			$legal = '<a href="' . $this->appSettings->getUseImprintUrl() . '">' .  $this->l10n->t('Legal Notice') . '</a>';
		}
		if ($this->appSettings->getUsePrivacyUrl()) {
			if ($this->appSettings->getUseImprintUrl()) {
				$legal = $legal . ' | ';
			}

			$legal = $legal . '<a href="' . $this->appSettings->getUsePrivacyUrl() . '">' .  $this->l10n->t('Privacy Policy') . '</a>';
		}
		return $legal;
	}

	protected function getUser(string $userId) : UserBase {
		if ($this->userManager->get($userId) instanceof IUser) {
			// return User object
			return new User($userId);
		}
		// return UserBaseChild from share
		return Container::findShare($this->poll->getId(), $userId)->getUserObject();
	}

	protected function getRichDescription() : string {
		return $this->getParsedMarkDown($this->poll->getDescription());
	}

	protected function getParsedMarkDown(string $source) : string {
		$config = [
			'renderer' => [
				'soft_break' => "<br />",
			],
			'html_input' => 'strip',
			'allow_unsafe_links' => false,
		];

		$environment = new Environment($config);
		$environment->addExtension(new CommonMarkCoreExtension());
		$environment->addExtension(new TableExtension());
		$converter = new MarkdownConverter($environment);
		return $converter->convertToHtml($source)->getContent();
	}

	private function getShareURL() : string {
		return Container::findShare($this->poll->getId(), $this->recipient->getId())->getURL();
	}

	private function getPoll(int $pollId) : Poll {
		return Container::queryPoll($pollId);
	}

	private function validateEmailAddress(): void {
		if (!$this->recipient->getEmailAddress()
			|| !filter_var($this->recipient->getEmailAddress(), FILTER_VALIDATE_EMAIL)) {
			throw new InvalidEmailAddress('Invalid email address (' . $this->recipient->getEmailAddress() . ')');
		}
	}
}
