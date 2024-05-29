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
class AutoReminderCron extends TimedJob {
	public function __construct(
		protected ITimeFactory $time,
		private MailService $mailService,
	) {
		parent::__construct($time);
		parent::setInterval(30); // run every 30 minutes
	}

	/**
	 * @param mixed $argument
	 * @return void
	 */
	protected function run($argument) {
		$this->mailService->sendAutoReminder();
	}

	public function manuallyRun(): string {
		$this->run(null);
		return 'AutoReminderCron manually run.';
	}
}
