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

	/** @var string */
	protected $shareType;

	/** @var string */
	protected $userIsActor;

	/** @var string */
	protected $eventType;

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

	public function getActivityMessage(ActivityEvent $event, string $language, bool $filtered = false) : string {
		$this->trans = $this->transFactory->get($event->getApp(), $language);
		$this->userIsActor = $event->getAuthor() === \OC::$server->getUserSession()->getUser()->getUID();
		$this->eventType = $event->getType();
		$parameters = $event->getSubjectParameters();
		$this->shareType = $parameters['shareType']['name'] ?? '';

		return $filtered
			? $this->getMessageFiltered()
			: $this->getMessageFull();
	}

	private function getMessageFull() {
		switch ($this->eventType) {
			case CommentEvent::ADD:
				return $this->userIsActor
					? $this->trans->t('You commented poll {pollTitle}')
					: $this->trans->t('{actor} commented poll {pollTitle}');
			case CommentEvent::DELETE:
				return $this->userIsActor
					? $this->trans->t('You deleted a commented from poll {pollTitle}')
					: $this->trans->t('{actor} deleted a commented from poll {pollTitle}');

			case OptionEvent::ADD:
				return $this->userIsActor
					? $this->trans->t('You added an option to poll {pollTitle}')
					: $this->trans->t('{actor} added an option to poll {pollTitle}');
			case OptionEvent::UPDATE:
				return $this->userIsActor
					? $this->trans->t('You changed an option of poll {pollTitle}')
					: $this->trans->t('{actor} changed an option of poll {pollTitle}');
			case OptionEvent::CONFIRM:
				return $this->userIsActor
					? $this->trans->t('You confirmed option {optionTitle} of poll {pollTitle}')
					: $this->trans->t('{actor} confirmed option {optionTitle} of poll {pollTitle}');
			case OptionEvent::UNCONFIRM:
				return $this->userIsActor
					? $this->trans->t('You unconfirmed option {optionTitle} of poll {pollTitle}')
					: $this->trans->t('{actor} unconfirmed option {optionTitle} of poll {pollTitle}');
			case OptionEvent::DELETE:
				return $this->userIsActor
					? $this->trans->t('You removed option {optionTitle} from poll {pollTitle}')
					: $this->trans->t('{actor} removed option {optionTitle} from poll {pollTitle}');

			case PollEvent::ADD:
				return $this->userIsActor
					? $this->trans->t('You added poll {pollTitle}')
					: $this->trans->t('{actor} added poll {pollTitle}');
			case PollEvent::UPDATE:
				return $this->userIsActor
					? $this->trans->t('You changed the configuration of poll {pollTitle}')
					: $this->trans->t('{actor} changed the configuration of poll {pollTitle}');
			case PollEvent::DELETE:
				return $this->userIsActor
					? $this->trans->t('You archived poll {pollTitle}')
					: $this->trans->t('{actor} archived poll {pollTitle}');
			case PollEvent::RESTORE:
				return $this->userIsActor
					? $this->trans->t('You restored poll {pollTitle}')
					: $this->trans->t('{actor} restored poll {pollTitle}');
			case PollEvent::EXPIRE:
				return $this->userIsActor
					? $this->trans->t('Poll {pollTitle} was closed')
					: $this->trans->t('Poll {pollTitle} was closed');
			case PollEvent::OWNER_CHANGE:
				return $this->userIsActor
					? $this->trans->t('You changed the owner of poll {pollTitle}')
					: $this->trans->t('{actor} changed the owner of poll {pollTitle}');
			case PollEvent::OPTION_REORDER:
				return $this->userIsActor
					? $this->trans->t('You reordered the options of poll {pollTitle}')
					: $this->trans->t('{actor} reordered the options of poll {pollTitle}');

			case ShareEvent::ADD:
				if ($this->shareType === Share::TYPE_PUBLIC) {
					return $this->userIsActor
						? $this->trans->t('You added a public share to poll {pollTitle}')
						: $this->trans->t('{actor} added a public share to poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_GROUP) {
					return $this->userIsActor
						? $this->trans->t('You shared poll {pollTitle} with group {sharee}')
						: $this->trans->t('{actor} You shared poll {pollTitle} with group {sharee}');
				} elseif ($this->shareType === Share::TYPE_CIRCLE) {
					return $this->userIsActor
						? $this->trans->t('You shared poll {pollTitle} with circle {sharee}')
						: $this->trans->t('{actor} shared poll {pollTitle} with circle {sharee}');
				} elseif ($this->shareType === Share::TYPE_CONTACTGROUP) {
					return $this->userIsActor
						? $this->trans->t('You shared poll {pollTitle} with contact group {sharee}')
						: $this->trans->t('{actor} shared poll {pollTitle} with contact group {sharee}');
				}
				return $this->userIsActor
					? $this->trans->t('You shared poll {pollTitle} with {sharee}')
					: $this->trans->t('{actor} shared poll {pollTitle} with {sharee}');
			case ShareEvent::CHANGE_EMAIL:
				return $this->userIsActor
					? $this->trans->t('You changed your email address')
					: $this->trans->t('{actor} changed his email address');
			case ShareEvent::CHANGE_TYPE:
				return $this->userIsActor
					? $this->trans->t('You changed the share type')
					: $this->trans->t('{actor} changed the share type');
			case ShareEvent::CHANGE_REG_CONSTR:
				return $this->userIsActor
					? $this->trans->t('You changed the registration constraints for share {sharee}')
					: $this->trans->t('{actor} changed the registration constraints for share {sharee}');
			case ShareEvent::REGISTRATION:
				return $this->userIsActor
					? $this->trans->t('You registered to poll {pollTitle}')
					: $this->trans->t('{sharee} registered to poll {pollTitle}');
			case ShareEvent::DELETE:
				if ($this->shareType === Share::TYPE_PUBLIC) {
					return $this->userIsActor
						? $this->trans->t('You deleted a public share from poll {pollTitle}')
						: $this->trans->t('{actor} deleted a public share from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_GROUP) {
					return $this->userIsActor
						? $this->trans->t('You deleted the share for group {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for group {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_USER) {
					return $this->userIsActor
						? $this->trans->t('You deleted the share for {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_EMAIL) {
					return $this->userIsActor
						? $this->trans->t('You deleted the share for {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_CONTACT) {
					return $this->userIsActor
						? $this->trans->t('You deleted the share for {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_EXTERNAL) {
					return $this->userIsActor
						? $this->trans->t('You deleted the share for {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_CIRCLE) {
					return $this->userIsActor
						? $this->trans->t('You deleted the share for circle {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for circle {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_CONTACTGROUP) {
					return $this->userIsActor
						? $this->trans->t('You deleted the share for contact group {sharee} from poll {pollTitle}')
						: $this->trans->t('{actor} deleted the share for contact group {sharee} from poll {pollTitle}');
				}

				return $this->userIsActor
					? $this->trans->t('You deleted a share from poll {pollTitle}')
					: $this->trans->t('{actor} deleted a share from poll {pollTitle}');

			case VoteEvent::SET:
				return $this->userIsActor
					? $this->trans->t('You voted in poll {pollTitle}')
					: $this->trans->t('{actor} voted in poll {pollTitle}');
		}

		return $this->userIsActor
			? $this->trans->t('You did something indescribable with poll {pollTitle}')
			: $this->trans->t('{actor} did something indescribable with poll {pollTitle}');
	}

	private function getMessageFiltered() {
		switch ($this->eventType) {
			case CommentEvent::ADD:
				return $this->userIsActor
					? $this->trans->t('You commented')
					: $this->trans->t('{actor} commented');
			case CommentEvent::DELETE:
				return $this->userIsActor
					? $this->trans->t('You deleted a commented')
					: $this->trans->t('{actor} deleted a commented');

			case OptionEvent::ADD:
				return $this->userIsActor
					? $this->trans->t('You added an option')
					: $this->trans->t('{actor} added an option');
			case OptionEvent::UPDATE:
				return $this->userIsActor
					? $this->trans->t('You changed an option')
					: $this->trans->t('{actor} changed an option');
			case OptionEvent::CONFIRM:
				return $this->userIsActor
					? $this->trans->t('You confirmed option {optionTitle}')
					: $this->trans->t('{actor} confirmed option {optionTitle}');
			case OptionEvent::UNCONFIRM:
				return $this->userIsActor
					? $this->trans->t('You unconfirmed option {optionTitle}')
					: $this->trans->t('{actor} unconfirmed option {optionTitle}');
			case OptionEvent::DELETE:
				return $this->userIsActor
					? $this->trans->t('You removed option {optionTitle}')
					: $this->trans->t('{actor} removed option {optionTitle}');

			case PollEvent::ADD:
				return $this->userIsActor
					? $this->trans->t('You created this poll')
					: $this->trans->t('{actor} created this poll');
			case PollEvent::UPDATE:
				return $this->userIsActor
					? $this->trans->t('You changed the configuration')
					: $this->trans->t('{actor} changed the configuration');
			case PollEvent::DELETE:
				return $this->userIsActor
					? $this->trans->t('You archived this poll')
					: $this->trans->t('{actor} archived this poll');
			case PollEvent::RESTORE:
				return $this->userIsActor
					? $this->trans->t('You restored this poll')
					: $this->trans->t('{actor} restored this poll');
			case PollEvent::EXPIRE:
				return $this->trans->t('This poll was closed');
			case PollEvent::OWNER_CHANGE:
				return $this->userIsActor
					? $this->trans->t('You changed the poll owner')
					: $this->trans->t('{actor} changed the poll owner');
			case PollEvent::OPTION_REORDER:
				return $this->userIsActor
					? $this->trans->t('You reordered the options')
					: $this->trans->t('{actor} reordered the options');

			case ShareEvent::ADD:
				if ($this->shareType === Share::TYPE_PUBLIC) {
					return $this->userIsActor
						? $this->trans->t('You added a public share')
						: $this->trans->t('{actor} added a public share');
				} elseif ($this->shareType === Share::TYPE_GROUP) {
					return $this->userIsActor
						? $this->trans->t('You shared this poll with group {sharee}')
						: $this->trans->t('{actor} You shared this poll with group {sharee}');
				} elseif ($this->shareType === Share::TYPE_CIRCLE) {
					return $this->userIsActor
						? $this->trans->t('You shared this poll with circle {sharee}')
						: $this->trans->t('{actor} shared this poll with circle {sharee}');
				} elseif ($this->shareType === Share::TYPE_CONTACTGROUP) {
					return $this->userIsActor
						? $this->trans->t('You shared this poll with contact group {sharee}')
						: $this->trans->t('{actor} shared this poll with contact group {sharee}');
				}
				return $this->userIsActor
					? $this->trans->t('You shared this poll with {sharee}')
					: $this->trans->t('{actor} shared this poll with {sharee}');
			case ShareEvent::CHANGE_EMAIL:
				return $this->userIsActor
					? $this->trans->t('You changed your email address')
					: $this->trans->t('{actor}\'s email address was changed');
			case ShareEvent::CHANGE_TYPE:
				return $this->userIsActor
					? $this->trans->t('You changed the share type')
					: $this->trans->t('{actor} changed the share type');
			case ShareEvent::CHANGE_REG_CONSTR:
				return $this->userIsActor
					? $this->trans->t('You changed the registration constraints for share {sharee}')
					: $this->trans->t('{actor} changed the registration constraints for share {sharee}');
			case ShareEvent::REGISTRATION:
				return $this->userIsActor
					? $this->trans->t('You registered')
					: $this->trans->t('{sharee} registered');
			case ShareEvent::DELETE:
				if ($this->shareType === Share::TYPE_PUBLIC) {
					return $this->userIsActor
						? $this->trans->t('You deleted a public share')
						: $this->trans->t('{actor} deleted a public share');
				} elseif ($this->shareType === Share::TYPE_GROUP) {
					return $this->userIsActor
						? $this->trans->t('You deleted the share for group {sharee}')
						: $this->trans->t('{actor} deleted the share for group {sharee}');
				} elseif ($this->shareType === Share::TYPE_CIRCLE) {
					return $this->userIsActor
						? $this->trans->t('You deleted the share for circle {sharee}')
						: $this->trans->t('{actor} deleted the share for circle {sharee}');
				} elseif ($this->shareType === Share::TYPE_CONTACTGROUP) {
					return $this->userIsActor
						? $this->trans->t('You deleted the share for contact group {sharee}')
						: $this->trans->t('{actor} deleted the share for contact group {sharee}');
				}

				return $this->userIsActor
					? $this->trans->t('You deleted {sharee}\'s share')
					: $this->trans->t('{actor} deleted a share');

			case VoteEvent::SET:
				return $this->userIsActor
					? $this->trans->t('You voted')
					: $this->trans->t('{actor} voted');
		}

		return $this->userIsActor
			? $this->trans->t('You did something indescribable with this poll')
			: $this->trans->t('{actor} did something indescribable with this poll');
	}
}
