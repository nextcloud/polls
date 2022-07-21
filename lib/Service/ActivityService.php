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

use OCA\Polls\Db\Share;
use OCA\Polls\Event\CommentEvent;
use OCA\Polls\Event\PollEvent;
use OCA\Polls\Event\OptionEvent;
use OCA\Polls\Event\ShareEvent;
use OCA\Polls\Event\VoteEvent;
use OCP\Activity\IEvent as ActivityEvent;
use OCP\Activity\IManager as ActivityManager;
use OCP\EventDispatcher\Event;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class ActivityService {
	/** @var ActivityManager */
	protected $activityManager;

	/** @var IFactory */
	protected $transFactory;

	/** @var IL10N */
	protected $l10n;

	/** @var IUserSession */
	private $userSession;

	/** @var string */
	protected $shareType;

	/** @var bool */
	protected $userIsActor;

	/** @var string */
	protected $eventType;

	public function __construct(
		IFactory $transFactory,
		IUserSession $userSession,
		ActivityManager $activityManager
	) {
		$this->activityManager = $activityManager;
		$this->transFactory = $transFactory;
		$this->userSession = $userSession;
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
		$this->l10n = $this->transFactory->get($event->getApp(), $language);
		try {
			$this->userIsActor = $event->getAuthor() === $this->userSession->getUser()->getUID();
		} catch (\Exception $e) {
			$this->userIsActor = false;
		}
		$this->eventType = $event->getType();
		$parameters = $event->getSubjectParameters();
		$this->shareType = $parameters['shareType']['name'] ?? '';

		return $filtered
			? $this->getMessageFiltered()
			: $this->getMessageFull();
	}

	private function getMessageFull(): string {
		switch ($this->eventType) {
			case CommentEvent::ADD:
				return $this->userIsActor
					? $this->l10n->t('You have commented on poll {pollTitle}')
					: $this->l10n->t('{actor} has commented on poll {pollTitle}');
			case CommentEvent::DELETE:
				return $this->userIsActor
					? $this->l10n->t('You have deleted a comment from poll {pollTitle}')
					: $this->l10n->t('{actor} has deleted a comment from poll {pollTitle}');

			case OptionEvent::ADD:
				return $this->userIsActor
					? $this->l10n->t('You have added an option to poll {pollTitle}')
					: $this->l10n->t('{actor} has added an option to poll {pollTitle}');
			case OptionEvent::UPDATE:
				return $this->userIsActor
					? $this->l10n->t('You have changed an option of poll {pollTitle}')
					: $this->l10n->t('{actor} has changed an option of poll {pollTitle}');
			case OptionEvent::CONFIRM:
				return $this->userIsActor
					? $this->l10n->t('You have confirmed option {optionTitle} of poll {pollTitle}')
					: $this->l10n->t('{actor} has confirmed option {optionTitle} of poll {pollTitle}');
			case OptionEvent::UNCONFIRM:
				return $this->userIsActor
					? $this->l10n->t('You have unconfirmed option {optionTitle} of poll {pollTitle}')
					: $this->l10n->t('{actor} has unconfirmed option {optionTitle} of poll {pollTitle}');
			case OptionEvent::DELETE:
				return $this->userIsActor
					? $this->l10n->t('You have removed option {optionTitle} from poll {pollTitle}')
					: $this->l10n->t('{actor} has removed option {optionTitle} from poll {pollTitle}');

			case PollEvent::ADD:
				return $this->userIsActor
					? $this->l10n->t('You have added poll {pollTitle}')
					: $this->l10n->t('{actor} has added poll {pollTitle}');
			case PollEvent::UPDATE:
				return $this->userIsActor
					? $this->l10n->t('You have changed the configuration of poll {pollTitle}')
					: $this->l10n->t('{actor} has changed the configuration of poll {pollTitle}');
			case PollEvent::DELETE:
				return $this->userIsActor
					? $this->l10n->t('You have archived poll {pollTitle}')
					: $this->l10n->t('{actor} has archived poll {pollTitle}');
			case PollEvent::RESTORE:
				return $this->userIsActor
					? $this->l10n->t('You have restored poll {pollTitle}')
					: $this->l10n->t('{actor} has restored poll {pollTitle}');
			case PollEvent::EXPIRE:
				return $this->l10n->t('Poll {pollTitle} has been closed');
			case PollEvent::OWNER_CHANGE:
				return $this->userIsActor
					? $this->l10n->t('You have changed the owner of poll {pollTitle}')
					: $this->l10n->t('{actor} has changed the owner of poll {pollTitle}');
			case PollEvent::OPTION_REORDER:
				return $this->userIsActor
					? $this->l10n->t('You have reordered the options of poll {pollTitle}')
					: $this->l10n->t('{actor} has reordered the options of poll {pollTitle}');

			case ShareEvent::ADD:
				if ($this->shareType === Share::TYPE_PUBLIC) {
					return $this->userIsActor
						? $this->l10n->t('You have added a public share to poll {pollTitle}')
						: $this->l10n->t('{actor} has added a public share to poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_GROUP) {
					return $this->userIsActor
						? $this->l10n->t('You have shared poll {pollTitle} with group {sharee}')
						: $this->l10n->t('{actor} has shared poll {pollTitle} with group {sharee}');
				} elseif ($this->shareType === Share::TYPE_CIRCLE) {
					return $this->userIsActor
						? $this->l10n->t('You have shared poll {pollTitle} with circle {sharee}')
						: $this->l10n->t('{actor} has shared poll {pollTitle} with circle {sharee}');
				} elseif ($this->shareType === Share::TYPE_CONTACTGROUP) {
					return $this->userIsActor
						? $this->l10n->t('You have shared poll {pollTitle} with contact group {sharee}')
						: $this->l10n->t('{actor} has shared poll {pollTitle} with contact group {sharee}');
				}
				return $this->userIsActor
					? $this->l10n->t('You have shared poll {pollTitle} with {sharee}')
					: $this->l10n->t('{actor} has shared poll {pollTitle} with {sharee}');
			case ShareEvent::CHANGE_EMAIL:
				return $this->userIsActor
					? $this->l10n->t('You have changed your email address')
					: $this->l10n->t('{sharee} has changed his email address');
			case ShareEvent::CHANGE_DISPLAY_NAME:
				return $this->userIsActor
					? $this->l10n->t('You have changed your name')
					: $this->l10n->t('{sharee} has changed his name');
			case ShareEvent::CHANGE_TYPE:
				return $this->userIsActor
					? $this->l10n->t('You have changed the share type')
					: $this->l10n->t('{actor} has changed the share type');
			case ShareEvent::CHANGE_REG_CONSTR:
				return $this->userIsActor
					? $this->l10n->t('You have changed the registration constraints for share {sharee}')
					: $this->l10n->t('{actor} has changed the registration constraints for share {sharee}');
			case ShareEvent::REGISTRATION:
				return $this->userIsActor
					? $this->l10n->t('You have registered to poll {pollTitle}')
					: $this->l10n->t('{sharee} registered to poll {pollTitle}');
			case ShareEvent::DELETE:
				if ($this->shareType === Share::TYPE_PUBLIC) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted a public share from poll {pollTitle}')
						: $this->l10n->t('{actor} has deleted a public share from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_GROUP) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted the share for group {sharee} from poll {pollTitle}')
						: $this->l10n->t('{actor} has deleted the share for group {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_USER) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted the share for {sharee} from poll {pollTitle}')
						: $this->l10n->t('{actor} has deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_EMAIL) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted the share for {sharee} from poll {pollTitle}')
						: $this->l10n->t('{actor} has deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_CONTACT) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted the share for {sharee} from poll {pollTitle}')
						: $this->l10n->t('{actor} has deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_EXTERNAL) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted the share for {sharee} from poll {pollTitle}')
						: $this->l10n->t('{actor} has deleted the share for {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_CIRCLE) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted the share for circle {sharee} from poll {pollTitle}')
						: $this->l10n->t('{actor} has deleted the share for circle {sharee} from poll {pollTitle}');
				} elseif ($this->shareType === Share::TYPE_CONTACTGROUP) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted the share for contact group {sharee} from poll {pollTitle}')
						: $this->l10n->t('{actor} has deleted the share for contact group {sharee} from poll {pollTitle}');
				}

				return $this->userIsActor
					? $this->l10n->t('You have deleted a share from poll {pollTitle}')
					: $this->l10n->t('{actor} has deleted a share from poll {pollTitle}');

			case VoteEvent::SET:
				return $this->userIsActor
					? $this->l10n->t('You have voted in poll {pollTitle}')
					: $this->l10n->t('{actor} has voted in poll {pollTitle}');
		}

		return $this->userIsActor
			? $this->l10n->t('You have done something indescribable with poll {pollTitle}')
			: $this->l10n->t('{actor} has done something indescribable with poll {pollTitle}');
	}

	private function getMessageFiltered(): string {
		switch ($this->eventType) {
			case CommentEvent::ADD:
				return $this->userIsActor
					? $this->l10n->t('You have commented')
					: $this->l10n->t('{actor} has commented');
			case CommentEvent::DELETE:
				return $this->userIsActor
					? $this->l10n->t('You have deleted a comment')
					: $this->l10n->t('{actor} has deleted a comment');

			case OptionEvent::ADD:
				return $this->userIsActor
					? $this->l10n->t('You have added an option')
					: $this->l10n->t('{actor} has added an option');
			case OptionEvent::UPDATE:
				return $this->userIsActor
					? $this->l10n->t('You have changed an option')
					: $this->l10n->t('{actor} has changed an option');
			case OptionEvent::CONFIRM:
				return $this->userIsActor
					? $this->l10n->t('You have confirmed option {optionTitle}')
					: $this->l10n->t('{actor} has confirmed option {optionTitle}');
			case OptionEvent::UNCONFIRM:
				return $this->userIsActor
					? $this->l10n->t('You have unconfirmed option {optionTitle}')
					: $this->l10n->t('{actor} has unconfirmed option {optionTitle}');
			case OptionEvent::DELETE:
				return $this->userIsActor
					? $this->l10n->t('You have removed option {optionTitle}')
					: $this->l10n->t('{actor} has removed option {optionTitle}');

			case PollEvent::ADD:
				return $this->userIsActor
					? $this->l10n->t('You have created this poll')
					: $this->l10n->t('{actor} has created this poll');
			case PollEvent::UPDATE:
				return $this->userIsActor
					? $this->l10n->t('You have changed the configuration')
					: $this->l10n->t('{actor} has changed the configuration');
			case PollEvent::DELETE:
				return $this->userIsActor
					? $this->l10n->t('You have archived this poll')
					: $this->l10n->t('{actor} has archived this poll');
			case PollEvent::RESTORE:
				return $this->userIsActor
					? $this->l10n->t('You have restored this poll')
					: $this->l10n->t('{actor} has restored this poll');
			case PollEvent::EXPIRE:
				return $this->l10n->t('This poll has been closed');
			case PollEvent::OWNER_CHANGE:
				return $this->userIsActor
					? $this->l10n->t('You have changed the poll owner')
					: $this->l10n->t('{actor} has changed the poll owner');
			case PollEvent::OPTION_REORDER:
				return $this->userIsActor
					? $this->l10n->t('You have reordered the options')
					: $this->l10n->t('{actor} has reordered the options');

			case ShareEvent::ADD:
				if ($this->shareType === Share::TYPE_PUBLIC) {
					return $this->userIsActor
						? $this->l10n->t('You have added a public share')
						: $this->l10n->t('{actor} has added a public share');
				} elseif ($this->shareType === Share::TYPE_GROUP) {
					return $this->userIsActor
						? $this->l10n->t('You have shared this poll with group {sharee}')
						: $this->l10n->t('{actor} has shared this poll with group {sharee}');
				} elseif ($this->shareType === Share::TYPE_CIRCLE) {
					return $this->userIsActor
						? $this->l10n->t('You have shared this poll with circle {sharee}')
						: $this->l10n->t('{actor} has shared this poll with circle {sharee}');
				} elseif ($this->shareType === Share::TYPE_CONTACTGROUP) {
					return $this->userIsActor
						? $this->l10n->t('You have shared this poll with contact group {sharee}')
						: $this->l10n->t('{actor} has shared this poll with contact group {sharee}');
				}
				return $this->userIsActor
					? $this->l10n->t('You have shared this poll with {sharee}')
					: $this->l10n->t('{actor} has shared this poll with {sharee}');
			case ShareEvent::CHANGE_EMAIL:
				return $this->userIsActor
					? $this->l10n->t('You have changed your email address')
					: $this->l10n->t('Email address of {sharee} has been changed');
			case ShareEvent::CHANGE_DISPLAY_NAME:
				return $this->userIsActor
					? $this->l10n->t('You have changed your name')
					: $this->l10n->t('Display name of {sharee} has been changed');
			case ShareEvent::CHANGE_TYPE:
				return $this->userIsActor
					? $this->l10n->t('You have changed the share type')
					: $this->l10n->t('{actor} has changed the share type');
			case ShareEvent::CHANGE_REG_CONSTR:
				return $this->userIsActor
					? $this->l10n->t('You have changed the registration constraints for share {sharee}')
					: $this->l10n->t('{actor} has changed the registration constraints for share {sharee}');
			case ShareEvent::REGISTRATION:
				return $this->userIsActor
					? $this->l10n->t('You have registered')
					: $this->l10n->t('{sharee} has registered');
			case ShareEvent::DELETE:
				if ($this->shareType === Share::TYPE_PUBLIC) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted a public share')
						: $this->l10n->t('{actor} has deleted a public share');
				} elseif ($this->shareType === Share::TYPE_GROUP) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted the share for group {sharee}')
						: $this->l10n->t('{actor} has deleted the share for group {sharee}');
				} elseif ($this->shareType === Share::TYPE_CIRCLE) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted the share for circle {sharee}')
						: $this->l10n->t('{actor} has deleted the share for circle {sharee}');
				} elseif ($this->shareType === Share::TYPE_CONTACTGROUP) {
					return $this->userIsActor
						? $this->l10n->t('You have deleted the share for contact group {sharee}')
						: $this->l10n->t('{actor} has deleted the share for contact group {sharee}');
				}

				return $this->userIsActor
					? $this->l10n->t('You have deleted share of {sharee}')
					: $this->l10n->t('{actor} has deleted a share');

			case VoteEvent::SET:
				return $this->userIsActor
					? $this->l10n->t('You have voted')
					: $this->l10n->t('{actor} has voted');
		}

		return $this->userIsActor
			? $this->l10n->t('You have done something indescribable with this poll')
			: $this->l10n->t('{actor} has done something indescribable with this poll');
	}
}
