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

use OCP\AppFramework\Db\DoesNotExistException;

use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Service\NotificationService;

class Notifier implements INotifier {
	public const NOTIFY_POLL_DELETED_BY_OTHER = 'deletePollByOther';
	public const NOTIFY_POLL_ARCHIVED_BY_OTHER = 'softDeletePollByOther';
	public const NOTIFY_POLL_TAKEOVER = 'takeOverPoll';
	public const NOTIFY_INVITATION = 'invitation';

	/** @var IFactory */
	protected $l10nFactory;

	/** @var IURLGenerator */
	protected $url;

	/** @var IUserManager */
	protected $userManager;

	/** @var PollMapper */
	protected $pollMapper;

	/** @var NotificationService */
	private $notificationService;

	public function __construct(
		IFactory $l10nFactory,
		IURLGenerator $url,
		IUserManager $userManager,
		PollMapper $pollMapper,
		NotificationService $notificationService
	) {
		$this->l10nFactory = $l10nFactory;
		$this->url = $url;
		$this->userManager = $userManager;
		$this->pollMapper = $pollMapper;
		$this->notificationService = $notificationService;
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
	private function getActor($actorId): array {
		$actor = $this->userManager->get($actorId);
		return [
			'actor' => [
				'type' => 'user',
				'id' => $actor->getUID(),
				'name' => $actor->getDisplayName(),
			]
		];
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get('polls', $languageCode);
		if ($notification->getApp() !== 'polls') {
			throw new \InvalidArgumentException();
		}
		$parameters = $notification->getSubjectParameters();

		$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('polls', 'polls-black.svg')));

		try {
			$poll = $this->pollMapper->find(intval($notification->getObjectId()));
			$actor = $this->getActor($parameters['actor'] ?? $poll->getOwner());
			$pollTitle = $poll->getTitle();
			$notification->setLink($this->url->linkToRouteAbsolute(
				'polls.page.vote',
				['id' => $poll->getId()]
			));
		} catch (DoesNotExistException $e) {
			$poll = null;
			$pollTitle = $parameters['pollTitle'];
			$actor = $this->getActor($parameters['actor']);
		}

		if (isset($actor['actor'])) {
			$actor = $actor['actor'];
		}

		switch ($notification->getSubject()) {
			case self::NOTIFY_INVITATION:
				$notification->setParsedSubject($l->t('%s invited you to a poll', $actor['name']));
				$notification->setRichSubject($l->t('{actor} has invited you to the poll "%s".', $pollTitle), $actor);
				break;

			case self::NOTIFY_POLL_TAKEOVER:
				$notification->setParsedSubject($l->t('%s took over your poll', $actor['name']));
				$notification->setRichSubject($l->t('{actor} took over your poll "%s" and is the new owner.', $pollTitle), $actor);
				break;

			case self::NOTIFY_POLL_DELETED_BY_OTHER:
				$notification->setParsedSubject($l->t('%s deleted your poll', $actor['name']));
				$notification->setRichSubject($l->t('{actor} deleted your poll "%s".', $pollTitle), $actor);
				break;

			case self::NOTIFY_POLL_ARCHIVED_BY_OTHER:
				$notification->setParsedSubject($l->t('%s archived your poll', $actor['name']));
				$notification->setRichSubject($l->t('{actor} archived your poll "%s".', $pollTitle), $actor);
				break;

			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
		return $notification;
	}
}
