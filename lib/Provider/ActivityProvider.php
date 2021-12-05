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

namespace OCA\Polls\Provider;

use OCP\Activity\IProvider;
use OCP\L10N\IFactory;
use OCP\IURLGenerator;
use OCP\Activity\IManager as ActivityManager;
use OCP\Activity\IEventMerger;
use OCP\Activity\IEvent as ActivityEvent;

class ActivityProvider implements IProvider {
	/**
	 * @param IFactory $languageFactory
	 * @param IURLGenerator $url
	 * @param ActivityManager $activityManager
	 * @param IEventMerger $eventMerger
	 */
	public function __construct(
		IFactory $languageFactory,
		IURLGenerator $url,
		ActivityManager $activityManager,
		IEventMerger $eventMerger
	) {
		$this->languageFactory = $languageFactory;
		$this->url = $url;
		$this->activityManager = $activityManager;
		$this->eventMerger = $eventMerger;
	}

	public function parse($language, ActivityEvent $activityEvent, ?ActivityEvent $previousEvent = null) {
		if ($activityEvent->getApp() !== 'polls') {
			throw new \InvalidArgumentException();
		}

		$this->l = $this->languageFactory->get($activityEvent->getApp(), $language);

		if ($this->activityManager->isFormattingFilteredObject()) {
			$activityEvent->setIcon($this->url->getAbsoluteURL($this->url->imagePath($activityEvent->getApp(), 'app.svg')));
			try {
				return $activityEvent->setParsedSubject($this->l->t('Activity triggered (short)'));
			} catch (\InvalidArgumentException $e) {
			}
		}

		$this->setSubjects($activityEvent, $this->l->t('Activity triggered (long) {user}'));
		$activityEvent = $this->eventMerger->mergeEvents($activityEvent->getApp(), $activityEvent, $previousEvent);
		return $activityEvent;
	}

	protected function setSubjects(ActivityEvent $activityEvent, string $subject) {
		$parameter = [
			'type' => 'user',
			'id' => 'userId',
			'name' => 'Some random user',
		];

		$activityEvent->setParsedSubject(str_replace('{user}', $parameter['id'], $subject))
			->setRichSubject($subject, ['user' => $parameter]);
	}
}
