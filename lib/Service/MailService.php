<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

namespace OCA\Polls\Service;

use Exception;

use OCP\IUser;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Mail\IEMailTemplate;

use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Model\User;
use OCA\Polls\Model\Contact;
use OCA\Polls\Model\Group;

class MailService {

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IConfig */
	private $config;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IL10N */
	private $trans;

	/** @var IFactory */
	private $transFactory;

	/** @var IMailer */
	private $mailer;

	/** @var SubscriptionMapper */
	private $subscriptionMapper;

	/** @var ShareMapper */
	private $shareMapper;

	/** @var PollMapper */
	private $pollMapper;

	/** @var LogMapper */
	private $logMapper;

	/**
	 * MailService constructor.
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $trans
	 * @param IFactory $transFactory
	 * @param IMailer $mailer
	 * @param SubscriptionMapper $subscriptionMapper
	 * @param ShareMapper $shareMapper
	 * @param PollMapper $pollMapper
	 * @param LogMapper $logMapper
	 */

	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		IConfig $config,
		IURLGenerator $urlGenerator,
		IL10N $trans,
		IFactory $transFactory,
		IMailer $mailer,
		ShareMapper $shareMapper,
		SubscriptionMapper $subscriptionMapper,
		PollMapper $pollMapper,
		LogMapper $logMapper
	) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->urlGenerator = $urlGenerator;
		$this->trans = $trans;
		$this->transFactory = $transFactory;
		$this->mailer = $mailer;
		$this->shareMapper = $shareMapper;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->pollMapper = $pollMapper;
		$this->logMapper = $logMapper;
	}


	/**
	 * sendMail - Send eMail and evaluate recipient's mail address
	 * and displayname if $userId is a site user
	 * @param IEmailTemplate $emailTemplate
	 * @param String $userId
	 * @param String $emailAddress, ignored, when $userId is set
	 * @param String $displayName, ignored, when $userId is set
	 * @return String
	 */

	private function sendMail($emailTemplate, $userId = '', $emailAddress = '', $displayName = '') {
		if ($this->userManager->get($userId) instanceof IUser) {
			$emailAddress = \OC::$server->getConfig()->getUserValue($userId, 'settings', 'email');
			$displayName = $this->userManager->get($userId)->getDisplayName();
		}

		if (!$emailAddress || !filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
			throw new Exception('Invalid email address (' . $emailAddress . ')');
		}

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$emailAddress => $displayName]);
			$message->useTemplate($emailTemplate);
			$this->mailer->send($message);

			return null;
		} catch (\Exception $e) {
			\OC::$server->getLogger()->logException($e, ['app' => 'polls']);
			throw $e;
		}
	}


	/**
	 * @param integer $pollId
	 * @param string $userId
	 * @return string
	 */
	public function resolveEmailAddress($pollId, $userId) {
		if ($this->userManager->get($userId) instanceof IUser) {
			return \OC::$server->getConfig()->getUserValue($userId, 'settings', 'email');
		}

		// if $userId is no site user, eval via shares
		try {
			$share = $this->shareMapper->findByPollAndUser($pollId, $userId);
			if ($share->getUserEmail()) {
				return $share->getUserEmail();
			}
		} catch (\Exception $e) {
			// catch silently
		}
		return $userId;
	}


	/**
	 * @param Share $share
	 * @param String $defaultLang
	 * @param String $skipUser
	 * @return Array $recipients
	 */
	private function getRecipientsByShare($share, $defaultLang = 'en', $skipUser = null) {
		$recipients = [];

		$tokenLink = $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->linkToRoute(
				'polls.page.vote_publicpublic',
				['token' => $share->getToken()]
			)
		);

		$internalLink = $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->linkToRoute(
				'polls.page.indexvote',
				['id' => $share->getPollId()]
			)
		);

		if ($share->getType() === Share::TYPE_USER) {
			$user = new User($share->getUserId());
			$recipients[] = [
				'userId' => $user->getUserId(),
				'eMailAddress' => $user->getEmailAddress(),
				'displayName' => $user->getDisplayName(),
				'language' => $user->getLanguage(),
				'link' => $internalLink,
			];
		} elseif ($share->getType() === Share::TYPE_EMAIL) {
			$user = new Email($share->getUserId());

			$recipients[] = [
				'userId' => $user->getUserId(),
				'eMailAddress' => $user->getEmailAddress(),
				'displayName' => $user->getDisplayName(),
				'language' => $defaultLang,
				'link' => $tokenLink,
			];
		} elseif ($share->getType() === Share::TYPE_CONTACT) {
			$user = new Contact($share->getUserId());

			$recipients[] = [
				'userId' => $user->getUserId(),
				'eMailAddress' => $user->getEmailAddress(),
				'displayName' => $user->getDisplayname(),
				'language' => $defaultLang,
				'link' => $tokenLink,
			];
		} elseif ($share->getType() === Share::TYPE_EXTERNAL) {
			$recipients[] = [
				'userId' => $share->getUserId(),
				'eMailAddress' => $share->getUserEmail(),
				'displayName' => $share->getUserId(),
				'language' => $defaultLang,
				'link' => $tokenLink,
			];
		} elseif ($share->getType() === Share::TYPE_GROUP) {
			foreach ((new Group($share->getUserId()))->getMembers() as $user) {
				if ($skipUser === $user->getId() || !$user->isUserDisabled()) {
					continue;
				}

				$recipients[] = [
					'userId' => $user->getId(),
					'eMailAddress' => $user->getEmailAddress(),
					'displayName' => $user->getDisplayName(),
					'language' => $user->getLanguage(),
					'link' => $internalLink,
				];
			}
		}
		return $recipients;
	}

	/**
	 * @param string $token
	 */
	public function sendInvitationMail($token) {
		$share = $this->shareMapper->findByToken($token);
		$poll = $this->pollMapper->find($share->getPollId());
		$owner = $this->userManager->get($poll->getOwner());
		$sentMails = [];
		$abortedMails = [];

		$recipients = $this->getRecipientsByShare(
			$this->shareMapper->findByToken($token),
			$this->config->getUserValue($poll->getOwner(), 'core', 'lang'),
			$poll->getOwner()
		);

		foreach ($recipients as $recipient) {
			$trans = $this->transFactory->get('polls', $recipient['language']);


			$emailTemplate = $this->mailer->createEMailTemplate('polls.Invitation', [
				'owner' => $owner->getDisplayName(),
				'title' => $poll->getTitle(),
				'link' => $recipient['link']
			]);

			$emailTemplate->setSubject($trans->t('Poll invitation "%s"', $poll->getTitle()));
			$emailTemplate->addHeader();
			$emailTemplate->addHeading($trans->t('Poll invitation "%s"', $poll->getTitle()), false);

			$emailTemplate->addBodyText(str_replace(
				['{owner}', '{title}'],
				[$owner->getDisplayName(), $poll->getTitle()],
				$trans->t('{owner} invited you to take part in the poll "{title}"')
			));

			$emailTemplate->addBodyText($poll->getDescription());

			$emailTemplate->addBodyButton(
				htmlspecialchars($trans->t('Go to poll')),
				$recipient['link']
			);

			$emailTemplate->addBodyText($trans->t('This link gives you personal access to the poll named above. Press the button above or copy the following link and add it in your browser\'s location bar: '));
			$emailTemplate->addBodyText($recipient['link']);

			$emailTemplate->addFooter($trans->t('This email is sent to you, because you are invited to vote in this poll by the poll owner. At least your name or your email address is recorded in this poll. If you want to get removed from this poll, contact the site administrator or the initiator of this poll, where the mail is sent from.'));

			try {
				$this->sendMail(
					$emailTemplate,
					$recipient['userId'],
					$recipient['eMailAddress'],
					$recipient['displayName']
				);
				$share->setInvitationSent(time());
				$this->shareMapper->update($share);
				$sentMails[] = $recipient;
			} catch (Exception $e) {
				$abortedMails[] = $recipient;
				\OC::$server->getLogger()->alert('Error sending Mail to ' . json_encode($recipient));
			}
		}
		return ['sentMails' => $sentMails, 'abortedMails' => $abortedMails];
	}

	public function sendNotifications() {
		$subscriptions = [];
		$log = $this->logMapper->findUnprocessedPolls();

		foreach ($log as $logItem) {
			$subscriptions = array_merge($subscriptions, $this->subscriptionMapper->findAllByPoll($logItem->getPollId()));
		}

		$log = $this->logMapper->findUnprocessed();

		foreach ($subscriptions as $subscription) {
			$poll = $this->pollMapper->find($subscription->getPollId());
			$emailAddress = '';
			$displayName = '';

			if ($this->userManager->get($subscription->getUserId()) instanceof IUser) {
				$lang = $this->config->getUserValue($subscription->getUserId(), 'core', 'lang');
			} else {
				try {
					$emailAddress = $this->shareMapper->findByPollAndUser($subscription->getPollId(), $subscription->getUserId())->getUserEmail();
					$displayName = $subscription->getUserId();
					$lang = $this->config->getUserValue($poll->getOwner(), 'core', 'lang');
				} catch (\Exception $e) {
					continue;
				}
			}

			$trans = $this->transFactory->get('polls', $lang);

			$url = $this->urlGenerator->getAbsoluteURL(
				$this->urlGenerator->linkToRoute(
					'polls.page.indexvote',
					['id' => $subscription->getPollId()]
				)
			);

			$emailTemplate = $this->mailer->createEMailTemplate('polls.Invitation', [
				'title' => $poll->getTitle(),
				'link' => $url
			]);
			$emailTemplate->setSubject($trans->t('Polls App - New Activity'));
			$emailTemplate->addHeader();
			$emailTemplate->addHeading($trans->t('Polls App - New Activity'), false);
			$emailTemplate->addBodyText(str_replace(
				['{title}'],
				[$poll->getTitle()],
				$trans->t('"{title}" had recent activity: ')
			));

			foreach ($log as $logItem) {
				if ($logItem->getPollId() === $subscription->getPollId()) {
					if ($poll->getAnonymous() || $poll->getShowResults() !== "always") {
						$displayUser = $trans->t('A user');
					} elseif ($this->userManager->get($logItem->getUserId()) instanceof IUser) {
						$displayUser = $this->userManager->get($logItem->getUserId())->getDisplayName();
					} else {
						$displayUser = $logItem->getUserId();
					}

					if ($logItem->getMessage()) {
						$emailTemplate->addBodyText($logItem->getMessage());
					} elseif ($logItem->getMessageId() === Log::MSG_ID_SETVOTE) {
						$emailTemplate->addBodyText($trans->t(
							'- %s voted.',
							[$displayUser]
						));
					} elseif ($logItem->getMessageId() === Log::MSG_ID_UPDATEPOLL) {
						$emailTemplate->addBodyText($trans->t(
							'- %s updated the poll configuration. Please check your votes.',
							[$displayUser]
						));
					} elseif ($logItem->getMessageId() === Log::MSG_ID_DELETEPOLL) {
						$emailTemplate->addBodyText($trans->t(
							'- %s deleted the poll.',
							[$displayUser]
						));
					} elseif ($logItem->getMessageId() === Log::MSG_ID_RESTOREPOLL) {
						$emailTemplate->addBodyText($trans->t(
							'- %s restored the poll.',
							[$displayUser]
						));
					} elseif ($logItem->getMessageId() === Log::MSG_ID_EXPIREPOLL) {
						$emailTemplate->addBodyText($trans->t(
							'- The poll expired.',
							[$displayUser]
						));
					} elseif ($logItem->getMessageId() === Log::MSG_ID_ADDOPTION) {
						$emailTemplate->addBodyText($trans->t(
							'- %s added a vote option.',
							[$displayUser]
						));
					} elseif ($logItem->getMessageId() === Log::MSG_ID_DELETEOPTION) {
						$emailTemplate->addBodyText($trans->t(
							'- %s removed a vote option.',
							[$displayUser]
						));
					} else {
						$emailTemplate->addBodyText(
							$logItem->getMessageId() . " (" . $displayUser . ")"
						);
					}
				}

				$logItem->setProcessed(time());
				$this->logMapper->update($logItem);
			}

			$emailTemplate->addBodyButton(
				htmlspecialchars($trans->t('Go to poll')),
				$url,
				/** @scrutinizer ignore-type */ false
			);
			$emailTemplate->addFooter($trans->t('This email is sent to you, because you subscribed to notifications of this poll. To opt out, visit the poll and remove your subscription.'));

			try {
				$this->sendMail($emailTemplate, $subscription->getUserId(), $emailAddress, $displayName);
			} catch (Exception $e) {
				\OC::$server->getLogger()->alert('Error sending Mail to ' . $subscription->getUserId());
			}
		}
	}
}
