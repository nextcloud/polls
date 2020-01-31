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
use OCP\ILogger;

use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\Subscription;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\LogMapper;

class MailService {

	private $userManager;
	private $groupManager;
	private $config;
	private $urlGenerator;
	private $trans;
	private $transFactory;
	private $mailer;
	private $logger;

	private $shareMapper;
	private $subscriptionMapper;
	private $pollMapper;
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
	 * @param ILogger $logger
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
		ILogger $logger,
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
		$this->logger = $logger;
		$this->shareMapper = $shareMapper;
		$this->subscriptionMapper = $subscriptionMapper;
		$this->pollMapper = $pollMapper;
		$this->logMapper = $logMapper;
	}


	/**
	 * sendMail - Send eMail and evaluate recipient's mail address
	 * and displayname if $toUserId is a site user
	 * @param IEmailTemplate $emailTemplate
	 * @param String $toUserId
	 * @param String $toEmail
	 * @param String $toDisplayName
	 * @return String
	 */

	private function sendMail($emailTemplate, $toUserId = '', $toEmail = '', $toDisplayName = '') {

		if ($this->userManager->get($toUserId) instanceof IUser && !$toEmail) {
			$toEmail = \OC::$server->getConfig()->getUserValue($toUserId, 'settings', 'email');
			$toDisplayName = $this->userManager->get($toUserId)->getDisplayName();
		}

		if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
	   		throw new Exception('Invalid email address (' . $toEmail . ')');
		}

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$toEmail => $toDisplayName]);
			$message->useTemplate($emailTemplate);
			$this->mailer->send($message);

			return null;

		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => 'polls']);
			throw $e;
		}

	}

	/**
	 * @param Share $share
	 * @param String $defaultLang
	 * @param String $skipUser
	 * @return Array $recipients
	 */
	private function getRecipientsByShare($share, $defaultLang = 'en', $skipUser = null) {
		$recipients = [];
		$contactsManager = \OC::$server->getContactsManager();

		if ($share->getType() === 'user') {
			// $this->logger->debug('User share ' . json_encode($share));

			$recipients[] = array(
				'userId' => $share->getUserId(),
				'eMailAddress' => null,
				'displayName' => null,
				'language' => $this->config->getUserValue(
					$share->getUserId(),
					'core', 'lang'
				),
				'link' => $this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkToRoute(
						'polls.page.indexvote',
						array('id' => $share->getPollId())
					)
				)
			);

		} elseif ($share->getType() === 'contact') {
			// $this->logger->debug('Contact share ' . json_encode($share));
			$contacts = $contactsManager->search($share->getUserId(), array('FN'));
			if (is_array($contacts)) {
				$contact = $contacts[0];

				$recipients[] = array(
					'userId' => $share->getUserId(),
					'eMailAddress' => $contact['EMAIL'][0],
					'displayName' => $contact['FN'],
					'language' => $defaultLang,
					'link' => $this->urlGenerator->getAbsoluteURL(
						$this->urlGenerator->linkToRoute(
							'polls.page.vote_publicpublic',
							array('token' => $share->getToken())
						)
					)
				);
			} else {
				return;
			}

		} elseif ($share->getType() === 'external' || $share->getType() === 'mail') {
			// $this->logger->debug('External share ' . json_encode($share));

			$recipients[] = array(
				'userId' => $share->getUserId(),
				'eMailAddress' => $share->getUserEmail(),
				'displayName' => $share->getUserId(),
				'language' => $defaultLang,
				'link' => $this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkToRoute(
						'polls.page.vote_publicpublic',
						array('token' => $share->getToken())
					)
				)
			);

		} elseif ($share->getType() === 'group') {
			// $this->logger->debug('Group share ' . json_encode($share));

			$groupMembers = array_keys($this->groupManager->displayNamesInGroup($share->getUserId()));
			// $this->logger->debug('Members are ' . json_encode($groupMembers));

			foreach ($groupMembers as $member) {
				if ($skipUser === $member) {
					// $this->logger->debug('skip ' . $skipUser);
					continue;
				}
				// $this->logger->debug('add ' . $member);

				$recipients[] = array(
					'userId' => $member,
					'eMailAddress' => null,
					'displayName' => null,
					'language' => $this->config->getUserValue($share->getUserId(), 'core', 'lang'),
					'link' => $this->urlGenerator->getAbsoluteURL(
						$this->urlGenerator->linkToRoute(
							'polls.page.indexvote', ['id' => $share->getPollId()]
						)
					)
				);

			}
		}
		// $this->logger->debug('Recipients: ' . json_encode($recipients));

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
		// $this->logger->debug('Search users for token ' . $token);
		$recipients = $this->getRecipientsByShare(
			$this->shareMapper->findByToken($token),
			$this->config->getUserValue($poll->getOwner(), 'core', 'lang'),
			$poll->getOwner()
		);

		// $this->logger->debug('Found these recipients: ' . json_encode($recipients));
		foreach ($recipients as $recipient) {
			$trans = $this->transFactory->get('polls', $recipient['language']);

			// $this->logger->debug('Build eMailTemplate for  ' . $recipient['userId']);

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

			$emailTemplate->addBodyButton(
				htmlspecialchars($trans->t('Go to poll')),
				$recipient['link']
			);

			$emailTemplate->addFooter($trans->t('This email is sent to you, because you are invited to vote in this poll by the poll owner.'));

			try {
				// $this->logger->debug('Send Mail to ' . $recipient);

				$this->sendMail(
					$emailTemplate,
					$recipient['userId'],
					$recipient['eMailAddress'],
					$recipient['displayName']
				);
				$sentMails[] = $recipient;
			} catch (Exception $e) {
				$abortedMails[] = $recipient;
				$this->logger->alert('Error sending Mail to ' . json_encode($recipient));
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

			if ($this->userManager->get($subscription->getUserId()) instanceof IUser) {
				$lang = $this->config->getUserValue($subscription->getUserId(), 'core', 'lang');
			} else {
				continue;
			}

			$poll = $this->pollMapper->find($subscription->getPollId());
			$trans = $this->transFactory->get('polls', $lang);

			$url = $this->urlGenerator->getAbsoluteURL(
				$this->urlGenerator->linkToRoute(
					'polls.page.indexvote',
					array('id' => $subscription->getPollId())
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

					if ($this->userManager->get($logItem->getUserId()) instanceof IUser) {
						$displayUser = $this->userManager->get($logItem->getUserId())->getDisplayName();
					} else {
						$displayUser = $logItem->getUserId();
					}

					if ($logItem->getMessage()) {
						$emailTemplate->addBodyText($logItem->getMessage());

					} elseif ($logItem->getMessageId() === 'setVote') {
						$emailTemplate->addBodyText($trans->t(
							'- %s voted.',
							array($displayUser)
						));

					} elseif ($logItem->getMessageId() === 'updatePoll') {
						$emailTemplate->addBodyText($trans->t(
							'- %s updated the poll configuration. Please check your votes.',
							array($displayUser)
						));

					} elseif ($logItem->getMessageId() === 'deletePoll') {
						$emailTemplate->addBodyText($trans->t(
							'- %s deleted the poll.',
							array($displayUser)
						));

					} elseif ($logItem->getMessageId() === 'restorePoll') {
						$emailTemplate->addBodyText($trans->t(
							'- %s restored the poll.',
							array($displayUser)
						));

					} elseif ($logItem->getMessageId() === 'expirePoll') {
						$emailTemplate->addBodyText($trans->t(
							'- The poll expired.',
							array($displayUser)
						));

					} elseif ($logItem->getMessageId() === 'addOption') {
						$emailTemplate->addBodyText($trans->t(
							'- %s added a vote option.',
							array($displayUser)
						));

					} elseif ($logItem->getMessageId() === 'deleteOption') {
						$emailTemplate->addBodyText($trans->t(
							'- %s removed a vote option.',
							array($displayUser)
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
				$this->sendMail($emailTemplate, $subscription->getUserId());
			} catch (Exception $e) {
				$this->logger->alert('Error sending Mail to ' . $subscription->getUserId());
				// TODO: alert Owner
			}
		}
	}
}
