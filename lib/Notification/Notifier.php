<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Notification;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Service\NotificationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use Psr\Log\LoggerInterface;

class Notifier implements INotifier {
	public const NOTIFY_POLL_DELETED_BY_OTHER = 'deletePollByOther';
	public const NOTIFY_POLL_ARCHIVED_BY_OTHER = 'softDeletePollByOther';
	public const NOTIFY_POLL_TAKEOVER = 'takeOverPoll';
	public const NOTIFY_POLL_CHANGED_OWNER = 'PollChangedOwner';
	public const NOTIFY_INVITATION = 'invitation';
	private const SUBJECT_PARSED = 'parsedSubject';
	private const SUBJECT_RICH = 'richSubject';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		protected IFactory $l10nFactory,
		protected IURLGenerator $urlGenerator,
		protected PollMapper $pollMapper,
		private UserMapper $userMapper,
		private NotificationService $notificationService,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 */
	public function getID(): string {
		return AppConstants::APP_ID;
	}

	/**
	 * Human readable name describing the notifier
	 */
	public function getName(): string {
		return $this->l10nFactory->get(AppConstants::APP_ID)->t('Polls');
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get(AppConstants::APP_ID, $languageCode);
		if ($notification->getApp() !== AppConstants::APP_ID) {
			throw new \InvalidArgumentException();
		}
		$parameters = $notification->getSubjectParameters();

		$notification->setIcon(
			$this->urlGenerator->getAbsoluteURL(
				$this->urlGenerator->imagePath(AppConstants::APP_ID, 'polls-dark.svg')
			)
		);

		try {
			$poll = $this->pollMapper->get(intval($notification->getObjectId()));
		} catch (DoesNotExistException $e) {
			$this->logger->info('Notification silently removed, poll not found', [
				'notification' => $notification->getObjectId(),
				'error' => $e->getMessage(),
			]);
			$this->notificationService->removeNotification(intval($notification->getObjectId()));
			return $notification;
		}

		$actor = $this->userMapper->getUserFromUserBase($parameters['actor'] ?? $poll->getOwner());
		$pollTitle = $parameters['pollTitle'] ?? $poll->getTitle();
		$notification->setLink($poll->getVoteUrl());

		$subjects = match ($notification->getSubject()) {
			self::NOTIFY_INVITATION => [
				self::SUBJECT_PARSED => $l->t('%s invited you to a poll', $actor->getDisplayName()),
				self::SUBJECT_RICH => $l->t('{actor} has invited you to the poll "%s".', $pollTitle),
			],
			self::NOTIFY_POLL_TAKEOVER => [
				self::SUBJECT_PARSED => $l->t('%s took over your poll', $actor->getDisplayName()),
				self::SUBJECT_RICH => $l->t('{actor} took over your poll "%s" and is the new owner.', $pollTitle),
			],
			self::NOTIFY_POLL_CHANGED_OWNER => [
				self::SUBJECT_PARSED => $l->t('%s is the new owner of your poll. ', $parameters['newOwner']),
				self::SUBJECT_RICH => $l->t('{actor} transfered your poll "%s" to the new owner {newOwner}. You are no more the owner.', $pollTitle),
			],
			self::NOTIFY_POLL_DELETED_BY_OTHER => [
				self::SUBJECT_PARSED => $l->t('%s deleted your poll', $actor->getDisplayName()),
				self::SUBJECT_RICH => $l->t('{actor} deleted your poll "%s".', $pollTitle),
			],
			self::NOTIFY_POLL_ARCHIVED_BY_OTHER => [
				self::SUBJECT_PARSED => $l->t('%s archived your poll', $actor->getDisplayName()),
				self::SUBJECT_RICH => $l->t('{actor} archived your poll "%s".', $pollTitle),
			],
			// Unknown subject => Unknown notification => throw
			default => throw new \InvalidArgumentException(),
		};

		switch ($notification->getSubject()) {
			case self::NOTIFY_POLL_CHANGED_OWNER:
				$newOwner = $this->userMapper->getUserFromUserBase($parameters['newOwner']);
				// overwrite the subject with the new owner
				$notification->setParsedSubject(
					$l->t('%s is the new owner of your poll. ', $newOwner->getDisplayName())
				);

				$notification->setRichSubject(
					$subjects[self::SUBJECT_RICH],
					[
						'actor' => $actor->getRichObjectString(),
						'newOwner' => $newOwner->getRichObjectString(),
					]
				);
				break;

			default:
				$notification->setParsedSubject($subjects[self::SUBJECT_PARSED]);
				$notification->setRichSubject(
					$subjects[self::SUBJECT_RICH],
					[
						'actor' => $actor->getRichObjectString(),
					]
				);
				break;
		}

		return $notification;
	}
}
