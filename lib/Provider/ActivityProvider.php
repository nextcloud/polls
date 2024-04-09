<?php

declare(strict_types=1);
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

use InvalidArgumentException;
use OCA\Polls\AppConstants;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Service\ActivityService;
use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager as ActivityManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;

/**
 * @psalm-suppress UnusedClass
 */
class ActivityProvider implements IProvider {
	public function __construct(
		protected ActivityManager $activityManager,
		protected ActivityService $activityService,
		protected IEventMerger $eventMerger,
		protected IFactory $transFactory,
		protected IURLGenerator $urlGenerator,
		protected ShareMapper $shareMapper,
		protected IL10N $l10n,
		protected UserMapper $userMapper,
	) {
	}

	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== AppConstants::APP_ID) {
			throw new \InvalidArgumentException();
		}
		
		$this->l10n = $this->transFactory->get($event->getApp(), $language);
		$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath($event->getApp(), 'polls-dark.svg')));
		$subject = $this->activityService->getActivityMessage($event, $language, $this->activityManager->isFormattingFilteredObject());
		if (!$subject) {
			throw new InvalidArgumentException();
		}
		$this->setSubjects($event, $subject);
		return $event;
	}

	protected function setSubjects(IEvent $event, string $subject): void {
		$parameters = $event->getSubjectParameters();
		
		try {
			$actor = $this->userMapper->getParticipant($event->getAuthor(), $event->getObjectId());
			$parameters['actor'] = [
				'type' => $actor->getSimpleType(),
				'id' => $actor->getId(),
				'name' => $actor->getDisplayName(),
			];
		} catch (\Exception $e) {
			$parameters['actor'] = [
				'type' => 'guest',
				'id' => $event->getAuthor(),
				'name' => 'An unknown participant',
			];
		}


		$placeholders = $replacements = [];
		foreach ($parameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			$replacements[] = $parameter['name'];
		}
		$event->setParsedSubject(str_replace($placeholders, $replacements, $subject))
			->setRichSubject($subject, $parameters);
	}
}
