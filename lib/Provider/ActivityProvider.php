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
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Activity\IManager as ActivityManager;
use OCP\Activity\IEventMerger;
use OCP\Activity\IEvent;
use OCA\Polls\Service\ActivityService;

class ActivityProvider implements IProvider {
	/** @var IFactory */
	protected $transFactory;

	/** @var IL10N */
	protected $trans;

	/** @var ActivityManager */
	protected $activityManager;

	/** @var ActivityService */
	protected $activityService;

	/** @var IEventMerger */
	protected $eventMerger;

	/** @var IUserManager */
	private $userManager;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/**
	 * @param IFactory $transFactory
	 * @param IURLGenerator $urlGenerator
	 * @param ActivityManager $activityManager
	 * @param ActivityService $activityService
	 * @param IEventMerger $eventMerger
	 */
	public function __construct(
		ActivityManager $activityManager,
		IEventMerger $eventMerger,
		IFactory $transFactory,
		IURLGenerator $urlGenerator,
		IUserManager $userManager,
		ActivityService $activityService
	) {
		$this->activityManager = $activityManager;
		$this->activityService = $activityService;
		$this->eventMerger = $eventMerger;
		$this->transFactory = $transFactory;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
	}

	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== 'polls') {
			throw new \InvalidArgumentException();
		}

		$this->trans = $this->transFactory->get($event->getApp(), $language);
		$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath($event->getApp(), 'app.svg')));

		// TODO: Extend for filtered views
		if ($this->activityManager->isFormattingFilteredObject()) {
			try {
				return $event->setParsedSubject($this->trans->t('Some activity triggered'));
			} catch (\InvalidArgumentException $e) {
			}
		}

		$this->setSubjects($event, $this->activityService->getActivityMessage($event, $language));
		// $this - $this->eventMerger->mergeEvents($event->getApp(), $event, $previousEvent);
		return $event;
	}

	protected function setSubjects(IEvent $event, string $subject): void {
		$parameters = $event->getSubjectParameters();
		$actor = $this->userManager->get($event->getAuthor());

		if ($actor instanceof IUser) {
			$parameters['actor'] = [
				'type' => 'user',
				'id' => $actor->getUID(),
				'name' => $actor->getDisplayName(),
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
