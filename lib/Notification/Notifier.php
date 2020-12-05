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

use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCA\Polls\Db\PollMapper;

class Notifier implements INotifier {
	/** @var IFactory */
	protected $l10nFactory;
	/** @var IURLGenerator */
	protected $url;
	/** @var IUserManager */
	protected $userManager;
	/** @var PollMapper */
	protected $pollMapper;

	public function __construct(
		IFactory $l10nFactory,
		IURLGenerator $url,
		IUserManager $userManager,
		PollMapper $pollMapper
	) {
		$this->l10nFactory = $l10nFactory;
		$this->url = $url;
		$this->userManager = $userManager;
		$this->pollMapper = $pollMapper;
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

	public function prepare(INotification $notification, string $languageCode): INotification {
		$l = $this->l10nFactory->get('polls', $languageCode);
		if ($notification->getApp() !== 'polls') {
			throw new \InvalidArgumentException();
		}
		$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('polls', 'polls-black.svg')));
		switch ($notification->getSubject()) {
			case 'invitation':
				$poll = $this->pollMapper->find(intval($notification->getObjectId()));
				$owner = $this->userManager->get($poll->getOwner());

				$notification->setParsedSubject(
					$l->t('%s invited you to a poll', [$owner->getDisplayName()])
				);

				$notification->setRichSubject(
					$l->t('{user} has invited you to the poll "%s".', [$poll->getTitle()]),
					[
						'user' => [
							'type' => 'user',
							'id' => $poll->getOwner(),
							'name' => $owner->getDisplayName(),
						]
					]
				);
				$notification->setLink($this->url->linkToRouteAbsolute(
					'polls.page.vote',
					['id' => $poll->getId()]
				));
				break;
			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
		return $notification;
	}
}
