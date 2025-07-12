<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Cron;

use Exception;
use OCA\Polls\AppConstants;
use OCA\Polls\Service\MailService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\ISession;
use Psr\Log\LoggerInterface;

/**
 * @psalm-api
 */
class AutoReminderCron extends TimedJob {
	public function __construct(
		protected ITimeFactory $time,
		private MailService $mailService,
		private ISession $session,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		parent::setInterval(30); // run every 30 minutes
	}

	/**
	 * @param mixed $argument
	 * @return void
	 */
	protected function run($argument) {
		$this->session->set(AppConstants::SESSION_KEY_CRON_JOB, true);
		try {
			$this->mailService->sendAutoReminder();
		} catch (Exception $e) {
			$this->logger->error('AutoReminderCron: An error occurred while running the auto reminder cron:' . $e->getMessage());
			return;
		} finally {
			$this->session->remove(AppConstants::SESSION_KEY_CRON_JOB);
		}
	}

	public function manuallyRun(): string {
		$this->logger->info('AutoReminderCron manually run.');
		$this->run(null);
		return 'AutoReminderCron manually run.';
	}
}
