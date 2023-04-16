<?php
/**
 * @copyright Copyright (c) 2017 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Notification;

use OCA\Polls\Db\PollMapper;

use OCA\Polls\Service\NotificationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {
	public const NOTIFY_POLL_DELETED_BY_OTHER = 'deletePollByOther';
	public const NOTIFY_POLL_ARCHIVED_BY_OTHER = 'softDeletePollByOther';
	public const NOTIFY_POLL_TAKEOVER = 'takeOverPoll';
	public const NOTIFY_INVITATION = 'invitation';
	private const SUBJECT_PARSED = 'parsedSubject';
	private const SUBJECT_RICH = 'richSubject';

	public function __construct(
		protected IFactory $l10nFactory,
		protected IURLGenerator $urlGenerator,
		protected IUserManager $userManager,
		protected PollMapper $pollMapper,
		private NotificationService $notificationService
	) {
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 */
	public function getID(): string {
		return 'polls';
	}

	/**
	 * Human readable name describing the notifier
	 */
	public function getName(): string {
		return $this->l10nFactory->get('polls')->t('Polls');
	}

	/**
	 * @return string[][]
	 *
	 * @psalm-return array{actor: array{type: 'user', id: string, name: string}}
	 */
	private function getActor(string $actorId): array {
		$actor = $this->userManager->get($actorId);
		return [
			'actor' => [
				'type' => 'user',
				'id' => $actor?->getUID() ?? '',
				'name' => $actor?->getDisplayName() ?? '',
			]
		];
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get('polls', $languageCode);
		if ($notification->getApp() !== 'polls') {
			throw new \InvalidArgumentException();
		}
		$parameters = $notification->getSubjectParameters();

		$notification->setIcon(
			$this->urlGenerator->getAbsoluteURL(
				$this->urlGenerator->imagePath('polls', 'polls.svg')
			)
		);

		try {
			$poll = $this->pollMapper->find(intval($notification->getObjectId()));
			$actor = $this->getActor($parameters['actor'] ?? $poll->getOwner());
			$pollTitle = $poll->getTitle();
			$notification->setLink($poll->getVoteUrl());
		} catch (DoesNotExistException $e) {
			$this->notificationService->removeNotification(intval($notification->getObjectId()));
			return $notification;
		}

		if (isset($actor['actor'])) {
			$actor = $actor['actor'];
		}

		$subjects = match ($notification->getSubject()) {
			self::NOTIFY_INVITATION => [
				self::SUBJECT_PARSED => $l->t('%s invited you to a poll', $actor['name']),
				self::SUBJECT_RICH => $l->t('{actor} has invited you to the poll "%s".', $pollTitle),
			],
			self::NOTIFY_POLL_TAKEOVER => [
				self::SUBJECT_PARSED => $l->t('%s took over your poll', $actor['name']),
				self::SUBJECT_RICH => $l->t('{actor} took over your poll "%s" and is the new owner.', $pollTitle),
			],
			self::NOTIFY_POLL_DELETED_BY_OTHER => [
				self::SUBJECT_PARSED => $l->t('%s deleted your poll', $actor['name']),
				self::SUBJECT_RICH => $l->t('{actor} deleted your poll "%s".', $pollTitle),
			],
			self::NOTIFY_POLL_ARCHIVED_BY_OTHER => [
				self::SUBJECT_PARSED => $l->t('%s archived your poll', $actor['name']),
				self::SUBJECT_RICH => $l->t('{actor} archived your poll "%s".', $pollTitle),
			],
			// Unknown subject => Unknown notification => throw
			default => throw new \InvalidArgumentException(),
		};

		$notification->setParsedSubject($subjects[self::SUBJECT_PARSED]);
		$notification->setRichSubject($subjects[self::SUBJECT_RICH], $actor);

		return $notification;
	}
}
