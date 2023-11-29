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

use OCA\Polls\AppConstants;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Service\ActivityService;
use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager as ActivityManager;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;

class ActivityProvider implements IProvider {
	public function __construct(
		protected ActivityManager $activityManager,
		protected ActivityService $activityService,
		protected IEventMerger $eventMerger,
		protected IFactory $transFactory,
		protected IURLGenerator $urlGenerator,
		protected IUserManager $userManager,
		protected ShareMapper $shareMapper,
		protected IL10N $l10n,
	) {
	}

	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== AppConstants::APP_ID) {
			throw new \InvalidArgumentException();
		}
		
		$this->l10n = $this->transFactory->get($event->getApp(), $language);
		$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath($event->getApp(), 'polls-dark.svg')));
		$this->setSubjects($event, $this->activityService->getActivityMessage($event, $language, $this->activityManager->isFormattingFilteredObject()));
		return $event;
	}

	protected function setSubjects(IEvent $event, string $subject): void {
		$parameters = $event->getSubjectParameters();
		$actor = $this->userManager->get($event->getAuthor());

		try {
			if ($actor instanceof IUser) {
				$parameters['actor'] = [
					'type' => 'user',
					'id' => $actor->getUID(),
					'name' => $actor->getDisplayName(),
				];
			} else {
				try {
					$share = $this->shareMapper->findByPollAndUser($event->getObjectId(), $event->getAuthor());
				} catch (ShareNotFoundException $e) {
					// User seems to be probaly deleted, use fake share
					$share = $this->shareMapper->getReplacement($event->getObjectId(), $event->getAuthor());
				}
				$parameters['actor'] = [
					'type' => 'guest',
					'id' => $share->getUserId(),
					'name' => $share->getDisplayName(),
				];
			}
		} catch (\Exception $e) {
			$parameters['actor'] = [
				'type' => 'guest',
				'id' => $event->getAuthor(),
				'name' => 'An unknown user',
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
