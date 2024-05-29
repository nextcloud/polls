<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Cron;

use OCA\Polls\Service\MailService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/**
 * @psalm-api
 */
class NotificationCron extends TimedJob {
	public function __construct(
		protected ITimeFactory $time,
		private MailService $mailService
	) {
		parent::__construct($time);
		parent::setInterval(5); // run every 5 minutes
	}

	/**
	 * @param mixed $argument
	 * @return void
	 */
	protected function run($argument) {
		$this->mailService->sendNotifications();
	}

	public function manuallyRun(): string {
		$this->run(null);
		return 'NotificationCron manually run.';
	}
}
