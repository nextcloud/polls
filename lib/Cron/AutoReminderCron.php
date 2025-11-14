<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Cron;

use Exception;
use OCA\Polls\AppConstants;
use OCA\Polls\Attributes\ManuallyRunnableCronJob;
use OCA\Polls\Service\MailService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\ISession;
use Psr\Log\LoggerInterface;

/**
 * @psalm-api
 */
#[ManuallyRunnableCronJob]
class AutoReminderCron extends TimedJob {
	public function __construct(
		protected ITimeFactory $time,
		protected LoggerInterface $logger,
		private MailService $mailService,
		private ISession $session,
		protected bool $supportsManualRun = true,
	) {
		parent::__construct($time);
		parent::setInterval(300); // run every 5 minutes
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
			return;
		} finally {
			$this->session->remove(AppConstants::SESSION_KEY_CRON_JOB);
		}
	}

	public function manuallyRun(): string {
		$this->logger->Info('AutoReminderCron will manually run.');
		$this->run(null);
		return 'AutoReminderCron was manually executed.';
	}
}
