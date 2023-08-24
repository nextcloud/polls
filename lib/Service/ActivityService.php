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

use OCA\Polls\AppConstants;
use OCA\Polls\Db\Share;
use OCA\Polls\Event\BaseEvent;
use OCA\Polls\Event\CommentEvent;
use OCA\Polls\Event\OptionEvent;
use OCA\Polls\Event\PollEvent;
use OCA\Polls\Event\ShareEvent;
use OCA\Polls\Event\VoteEvent;
use OCP\Activity\IEvent as ActivityEvent;
use OCP\Activity\IManager as ActivityManager;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class ActivityService {
	protected const APP_ID = AppConstants::APP_ID;
	private ActivityEvent $activityEvent;
	private BaseEvent $baseEvent;

	private string $shareType = '';
	private bool $userIsActor = true;
	private const FIRST_PERSON_FULL = 'firstFull';
	private const THIRD_PERSON_FULL = 'thirdFull';
	private const FIRST_PERSON_FILTERED = 'firstFiltered';
	private const THIRD_PERSON_FILTERED = 'thirdFiltered';

	public function __construct(
		private ActivityManager $activityManager,
		private IL10N $l10n,
		private IFactory $transFactory,
		private IUserSession $userSession,
	) {
	}

	public function getActivityMessage(ActivityEvent $activityEvent, string $language, bool $filtered = false): string {
		$this->activityEvent = $activityEvent;
		$this->l10n = $this->transFactory->get($this->activityEvent->getApp(), $language);

		try {
			$this->userIsActor = $this->activityEvent->getAuthor() === $this->userSession->getUser()?->getUID();
		} catch (\Exception $e) {
			$this->userIsActor = false;
		}

		$parameters = $this->activityEvent->getSubjectParameters();
		$this->shareType = $parameters['shareType']['name'] ?? '';

		$messages = $this->getMatchedMessages();

		if ($filtered) {
			return $this->userIsActor ? $messages[self::FIRST_PERSON_FILTERED] : $messages[self::THIRD_PERSON_FILTERED];
		}

		return $this->userIsActor ? $messages[self::FIRST_PERSON_FULL] : $messages[self::THIRD_PERSON_FULL];
	}

	public function addActivity(BaseEvent $baseEvent): void {
		$this->baseEvent = $baseEvent;
		$this->createActivityEvent();
		$this->publishActivityEvent();
	}

	private function createActivityEvent(): void {
		$this->activityEvent = $this->activityManager->generateEvent();
		$this->activityEvent->setApp(AppConstants::APP_ID)
			->setType($this->baseEvent->getActivityType() ?? '')
			->setAuthor($this->baseEvent->getActor())
			->setObject($this->baseEvent->getActivityObjectType() ?? '', $this->baseEvent->getActivityObjectId())
			->setSubject($this->baseEvent->getActivityType() ?? '', $this->baseEvent->getActivitySubjectParams())
			->setTimestamp(time());
	}

	private function publishActivityEvent(): void {
		$this->activityEvent->setAffectedUser($this->baseEvent->getActor());
		$this->activityManager->publish($this->activityEvent);

		// add additional event for poll owner if not actor
		if ($this->baseEvent->getActor() !== $this->baseEvent->getPollOwner()) {
			$this->activityEvent->setAffectedUser($this->baseEvent->getPollOwner());
			$this->activityManager->publish($this->activityEvent);
		}
	}

	private function getMatchedMessages(): array {
		return match ($this->activityEvent->getType()) {
			CommentEvent::ADD => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have commented on poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has commented on poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have commented'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has commented'),
			],
			CommentEvent::DELETE => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have deleted a comment from poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has deleted a comment from poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have deleted a comment'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has deleted a comment'),
			],
			OptionEvent::ADD => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have added an option to poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has added an option to poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have added an option'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has added an option'),
			],
			OptionEvent::UPDATE => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have changed an option of poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has changed an option of poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have changed an option'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has changed an option'),
			],
			OptionEvent::CONFIRM => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have confirmed option {optionTitle} of poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has confirmed option {optionTitle} of poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have confirmed option {optionTitle}'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has confirmed option {optionTitle}'),
			],
			OptionEvent::UNCONFIRM => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have unconfirmed option {optionTitle} of poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has unconfirmed option {optionTitle} of poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have unconfirmed option {optionTitle}'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has unconfirmed option {optionTitle}'),
			],
			OptionEvent::DELETE => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have removed option {optionTitle} from poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has removed option {optionTitle} from poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have removed option {optionTitle}'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has removed option {optionTitle}'),
			],
			PollEvent::ADD => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have added poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has added poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have created this poll'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has created this poll'),
			],
			PollEvent::UPDATE => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have changed the configuration of poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has changed the configuration of poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have changed the configuration'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has changed the configuration'),
			],
			PollEvent::DELETE => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have archived poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has archived poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have archived this poll'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has archived this poll'),
			],
			PollEvent::RESTORE => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have restored poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has restored poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have restored this poll'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has restored this poll'),
			],
			PollEvent::EXPIRE => [
				self::FIRST_PERSON_FULL => $this->l10n->t('Poll {pollTitle} has been closed'),
				self::THIRD_PERSON_FULL => $this->l10n->t('Poll {pollTitle} has been closed'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('This poll has been closed'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('This poll has been closed'),
			],
			PollEvent::OWNER_CHANGE => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have changed the owner of poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has changed the owner of poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have changed the poll owner'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has changed the poll owner'),
			],
			PollEvent::OPTION_REORDER => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have reordered the options of poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has reordered the options of poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have reordered the options'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has reordered the options'),
			],
			ShareEvent::CHANGE_EMAIL => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have changed your email address'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{sharee} has changed the email address'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have changed your email address'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('Email address of {sharee} has been changed'),
			],
			ShareEvent::CHANGE_DISPLAY_NAME => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have changed your name'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{sharee} has changed the name'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have changed your name'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('Display name of {sharee} has been changed'),
			],
			ShareEvent::CHANGE_TYPE => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have changed the share type'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has changed the share type'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have changed the share type'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has changed the share type'),
			],
			ShareEvent::CHANGE_REG_CONSTR => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have changed the registration constraints for share {sharee}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has changed the registration constraints for share {sharee}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have changed the registration constraints for share {sharee}'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has changed the registration constraints for share {sharee}'),
			],
			ShareEvent::REGISTRATION => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have registered to poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{sharee} registered to poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have registered'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{sharee} has registered'),
			],
			VoteEvent::SET => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have voted in poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has voted in poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have voted'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has voted'),
			],
			ShareEvent::ADD => match ($this->shareType) {
				Share::TYPE_PUBLIC => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have added a public share to poll {pollTitle}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has added a public share to poll {pollTitle}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have added a public share'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has added a public share'),

				],
				Share::TYPE_GROUP => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have shared poll {pollTitle} with group {sharee}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has shared poll {pollTitle} with group {sharee}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have shared this poll with group {sharee}'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has shared this poll with group {sharee}'),

				],
				Share::TYPE_CIRCLE => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have shared poll {pollTitle} with circle {sharee}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has shared poll {pollTitle} with circle {sharee}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have shared this poll with circle {sharee}'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has shared this poll with circle {sharee}'),

				],
				Share::TYPE_CONTACTGROUP => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have shared poll {pollTitle} with contact group {sharee}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has shared poll {pollTitle} with contact group {sharee}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have shared this poll with contact group {sharee}'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has shared this poll with contact group {sharee}'),

				],
				default => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have shared poll {pollTitle} with {sharee}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has shared poll {pollTitle} with {sharee}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have shared this poll with {sharee}'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has shared this poll with {sharee}'),
				],
			},

			ShareEvent::DELETE => match ($this->shareType) {
				Share::TYPE_USER, Share::TYPE_EMAIL, Share::TYPE_CONTACT, Share::TYPE_EXTERNAL => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have deleted the share for {sharee} from poll {pollTitle}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has deleted the share for {sharee} from poll {pollTitle}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have deleted share of {sharee}'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has deleted a share'),
				],
				Share::TYPE_PUBLIC => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have deleted a public share from poll {pollTitle}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has deleted a public share from poll {pollTitle}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have deleted a public share'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has deleted a public share'),
				],
				Share::TYPE_GROUP => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have deleted the share for group {sharee} from poll {pollTitle}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has deleted the share for group {sharee} from poll {pollTitle}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have deleted the share for group {sharee}'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has deleted the share for group {sharee}'),
				],
				Share::TYPE_CIRCLE => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have deleted the share for circle {sharee} from poll {pollTitle}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has deleted the share for circle {sharee} from poll {pollTitle}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have deleted the share for circle {sharee}'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has deleted the share for circle {sharee}'),
				],
				Share::TYPE_CONTACTGROUP => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have deleted the share for contact group {sharee} from poll {pollTitle}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has deleted the share for contact group {sharee} from poll {pollTitle}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have deleted the share for contact group {sharee}'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has deleted the share for contact group {sharee}'),
				],
				default => [
					self::FIRST_PERSON_FULL => $this->l10n->t('You have deleted a share from poll {pollTitle}'),
					self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has deleted a share from poll {pollTitle}'),
					self::FIRST_PERSON_FILTERED => $this->l10n->t('You have deleted share of {sharee}'),
					self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has deleted a share'),
				],
			},
			default => [
				self::FIRST_PERSON_FULL => $this->l10n->t('You have done something indescribable with poll {pollTitle}'),
				self::THIRD_PERSON_FULL => $this->l10n->t('{actor} has done something indescribable with poll {pollTitle}'),
				self::FIRST_PERSON_FILTERED => $this->l10n->t('You have done something indescribable with this poll'),
				self::THIRD_PERSON_FILTERED => $this->l10n->t('{actor} has done something indescribable with this poll'),
			],
		};
	}
}
