<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Listener;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\Polls\Exceptions\InvalidClassException;
use OCA\Polls\Service\ActivityService;
use OCA\Polls\Service\LogService;
use OCA\Polls\Service\NotificationService;
use OCA\Polls\Service\WatchService;

abstract class BaseListener implements IEventListener {

	/** @var ActivityService */
	protected $activityService;

	/** @var IJobList */
	protected $jobList;

	/** @var LogService */
	protected $logService;

	/** @var NotificationService */
	protected $notificationService;

	/** @var WatchService */
	protected $watchService;

	/** @var Event */
	protected $event;

	/** @var array */
	protected $watchTables = [];

	public function __construct(
		ActivityService $activityService,
		IJobList $jobList,
		LogService $logService,
		NotificationService $notificationService,
		WatchService $watchService
	) {
		$this->activityService = $activityService;
		$this->jobList = $jobList;
		$this->logService = $logService;
		$this->notificationService = $notificationService;
		$this->watchService = $watchService;
	}

	public function handle(Event $event) : void {
		$this->event = $event;
		try {
			$this->checkClass();
			$this->addLog();
			$this->addActivity();
		} catch (InvalidClassException $e) {
			return;
		} catch (UniqueConstraintViolationException $e) {
			// TODO: skip adding new activity in some situations, if adding log throws exception
			// deprecated NC22
			$this->addActivity();
		} catch (Exception $e) {
			if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				// TODO: skip adding new activity in some situations, if adding log throws exception
				// since NC22
				$this->addActivity();
			} else {
				throw $e;
			}
		}

		// add a cron job, if necessary (i.e. for removed users and groups)
		$this->addCronJob();

		// if a direct notification should be sent
		$this->createNotification();

		// update watch tables to inform clients about poll changes (watch polling)
		$this->writeWatch();
	}

	/**
	 * Check the class of $event. This method has to be declared in every
	 * child class.
	 * @throws InvalidClassException
	 */
	protected function checkClass() : void {
		throw new InvalidClassException('child class must be checked in child class');
	}

	/**
	 * Default logging for email notifications.
	 * @throws UniqueConstraintViolationException
	 * @throws Exception
	 */
	protected function addLog() : void {
		if ($this->event->getLogId()) {
			$this->logService->setLog(
				$this->event->getPollId(),
				$this->event->getLogId(),
				$this->event->getActor()
			);
		}
	}

	/**
	 * No default, define in child class
	 */
	protected function createNotification() : void {
		return;
	}

	/**
	 * No default, define in child class
	 */
	protected function addCronJob() : void {
		return;
	}

	/**
	 * Default for activity notification.
	 */
	protected function addActivity() : void {
		if ($this->event->getActivityId()) {
			$activityEvent = $this->activityService->createActivityEvent($this->event);
			$this->activityService->publishActivityEvent($activityEvent, $this->event->getActor());
			if ($this->event->getActor() !== $this->event->getPollOwner()) {
				$this->activityService->publishActivityEvent($activityEvent, $this->event->getPollOwner());
			}
		}
	}

	/**
	 * Default for watch
	 * Tables to watch are defined in $this->watchTables
	 */
	protected function writeWatch() : void {
		foreach ($this->watchTables as $table) {
			$this->watchService->writeUpdate($this->event->getPollId(), $table);
		}
	}
}
