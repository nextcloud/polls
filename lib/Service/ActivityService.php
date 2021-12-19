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

namespace OCA\Polls\Service;

use OCP\Activity\IManager as ActivityManager;
use OCP\Activity\IEvent as ActivityEvent;
use OCP\EventDispatcher\Event;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCA\Polls\Db\Share;
use OCA\Polls\Event\CommentEvent;
use OCA\Polls\Event\PollEvent;
use OCA\Polls\Event\OptionEvent;
use OCA\Polls\Event\ShareEvent;
use OCA\Polls\Event\VoteEvent;

class ActivityService {
	/** @var ActivityManager */
	protected $activityManager;
	/** @var IFactory */
	protected $transFactory;
	/** @var IL10N */
	protected $trans;

	public function __construct(
		IFactory $transFactory,
		ActivityManager $activityManager
	) {
		$this->transFactory = $transFactory;
		$this->activityManager = $activityManager;
	}

	public function createActivityEvent(Event $event): ActivityEvent {
		$activityEvent = $this->activityManager->generateEvent();
		$activityEvent->setApp('polls')
			->setType($event->getActivityId())
			->setAuthor($event->getActor())
			->setObject($event->getActivityObject(), $event->getActivityObjectId())
			->setSubject($event->getActivitySubject(), $event->getActivitySubjectParams())
			->setTimestamp(time());
		return $activityEvent;
	}

	public function publishActivityEvent(ActivityEvent $activityEvent, string $userId): void {
		$activityEvent->setAffectedUser($userId);
		$this->activityManager->publish($activityEvent);
	}

	public function getActivityMessage(ActivityEvent $event, string $language) : string {
		$this->trans = $this->transFactory->get($event->getApp(), $language);
		$userIsActor = $event->getAuthor() === \OC::$server->getUserSession()->getUser()->getUID();
		$parameters = $event->getSubjectParameters();
		$shareType = $parameters['shareType']['name'] ?? '';

		switch ($event->getType()) {
			case CommentEvent::ADD:
				return $userIsActor
					? $this->trans->t('You commented poll {pollTitle}')
					: $this->trans->t('{actor} commented poll {pollTitle}');
			case CommentEvent::DELETE:
				return $userIsActor
					? $this->trans->t('You deleted a commented from poll {pollTitle}')
					: $this->trans->t('{actor} deleted a commented from poll {pollTitle}');

			case OptionEvent::ADD:
				return $userIsActor
					? $this->trans->t('You added an option to poll {pollTitle}')
					: $this->trans->t('{actor} added an option to poll {pollTitle}');
			case OptionEvent::UPDATE:
				return $userIsActor
					? $this->trans->t('You changed an option of poll {pollTitle}')
					: $this->trans->t('{actor} changed an option of poll {pollTitle}');
			case OptionEvent::CONFIRM:
				return $userIsActor
					? $this->trans->t('You confirmed option {optionTitle} in poll {pollTitle}')
					: $this->trans->t('{actor} confirmed option {optionTitle} in poll {pollTitle}');
			case OptionEvent::UNCONFIRM:
				return $userIsActor
					? $this->trans->t('You unconfirmed option {optionTitle} in poll {pollTitle}')
					: $this->trans->t('{actor} unconfirmed option {optionTitle} in poll {pollTitle}');
			case OptionEvent::DELETE:
				return $userIsActor
					? $this->trans->t('You removed option {optionTitle} from poll {pollTitle}')
					: $this->trans->t('{actor} removed option {optionTitle} from poll {pollTitle}');

			case PollEvent::ADD:
				return $userIsActor
					? $this->trans->t('You added poll {pollTitle}')
					: $this->trans->t('{actor} added poll {pollTitle}');
			case PollEvent::UPDATE:
				return $userIsActor
					? $this->trans->t('You changed poll {pollTitle}')
					: $this->trans->t('{actor} changed poll {pollTitle}');
			case PollEvent::DELETE:
				return $userIsActor
					? $this->trans->t('You archived poll {pollTitle}')
					: $this->trans->t('{actor} archived poll {pollTitle}');
			case PollEvent::RESTORE:
				return $userIsActor
					? $this->trans->t('You restored poll {pollTitle}')
					: $this->trans->t('{actor} restored poll {pollTitle}');
			case PollEvent::EXPIRE:
				return $userIsActor
					? $this->trans->t('Poll {pollTitle} was closed')
					: $this->trans->t('Poll {pollTitle} was closed');
			case PollEvent::OWNER_CHANGE:
				return $userIsActor
					? $this->trans->t('You changed the owner of poll {pollTitle}')
					: $this->trans->t('{actor} changed the owner of poll {pollTitle}');
			case PollEvent::OPTION_REORDER:
				return $userIsActor
					? $this->trans->t('You reordered the options of poll {pollTitle}')
					: $this->trans->t('{actor} reordered the options of poll {pollTitle}');

			case ShareEvent::ADD:
				if ($shareType === Share::TYPE_PUBLIC) {
					return $userIsActor
						? $this->trans->t('You added a public share in poll {pollTitle}')
						: $this->trans->t('{actor} added a public share in poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_GROUP) {
					return $userIsActor
						? $this->trans->t('You added a share for group {sharee} in poll {pollTitle}')
						: $this->trans->t('{actor} added a share for group {sharee} in poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_USER) {
					return $userIsActor
						? $this->trans->t('You added a share for {sharee} in poll {pollTitle}')
						: $this->trans->t('{actor} added a share for {sharee} in poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_EMAIL) {
					return $userIsActor
						? $this->trans->t('You added a share for {sharee} in poll {pollTitle}')
						: $this->trans->t('{actor} added a share for {sharee} in poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_CONTACT) {
					return $userIsActor
						? $this->trans->t('You added a share for {sharee} in poll {pollTitle}')
						: $this->trans->t('{actor} added a share for {sharee} in poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_EXTERNAL) {
					return $userIsActor
						? $this->trans->t('You added a share for {sharee} in poll {pollTitle}')
						: $this->trans->t('{actor} added a share for {sharee} in poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_CIRCLE) {
					return $userIsActor
						? $this->trans->t('You added a share for circle {sharee} in poll {pollTitle}')
						: $this->trans->t('{actor} added a share for circle {sharee} in poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_CONTACTGROUP) {
					return $userIsActor
						? $this->trans->t('You added a share for contact group {sharee} in poll {pollTitle}')
						: $this->trans->t('{actor} added a share for contact group {sharee} in poll {pollTitle}');
				}

				return $userIsActor
					? $this->trans->t('You added a share in poll {pollTitle}')
					: $this->trans->t('{actor} added a share in poll {pollTitle}');
			case ShareEvent::CHANGE_EMAIL:
				return $userIsActor
					? $this->trans->t('You changed your email address')
					: $this->trans->t('{actor} changed his email address');
			case ShareEvent::CHANGE_TYPE:
				return $userIsActor
					? $this->trans->t('You changed the share type')
					: $this->trans->t('{actor} changed the share type');
			case ShareEvent::CHANGE_REG_CONSTR:
				return $userIsActor
					? $this->trans->t('You changed the registration constraints')
					: $this->trans->t('{actor} changed the registration constraints');
			case ShareEvent::REGISTRATION:
				return $userIsActor
					? $this->trans->t('You registered to poll {pollTitle}')
					: $this->trans->t('{sharee} registered to poll {pollTitle}');
			case ShareEvent::DELETE:
				if ($shareType === Share::TYPE_PUBLIC) {
					return $userIsActor
						? $this->trans->t('You deleted a public share from poll {pollTitle}')
						: $this->trans->t('{actor} deleted a public share from poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_GROUP) {
					return $userIsActor
						? $this->trans->t('You deleted the share for group {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for group {sharee} from poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_USER) {
					return $userIsActor
						? $this->trans->t('You deleted the share for {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_EMAIL) {
					return $userIsActor
						? $this->trans->t('You deleted the share for {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_CONTACT) {
					return $userIsActor
						? $this->trans->t('You deleted the share for {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_EXTERNAL) {
					return $userIsActor
						? $this->trans->t('You deleted the share for {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_CIRCLE) {
					return $userIsActor
						? $this->trans->t('You deleted the share for circle {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for circle {sharee} from poll {pollTitle}');
				} elseif ($shareType === Share::TYPE_CONTACTGROUP) {
					return $userIsActor
						? $this->trans->t('You deleted the share for contact group {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for contact group {sharee} from poll {pollTitle}');
				}

				return $userIsActor
					? $this->trans->t('You deleted a share from poll {pollTitle}')
					: $this->trans->t('{actor} deleted a share from poll {pollTitle}');

			case VoteEvent::SET:
				return $userIsActor
					? $this->trans->t('You {actor} voted in poll {pollTitle}')
					: $this->trans->t('{actor} voted in poll {pollTitle}');
		}

		return $userIsActor
			? $this->trans->t('You did something indescribable with poll {pollTitle}')
			: $this->trans->t('{actor} did something indescribable with poll {pollTitle}');
	}
}
