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
class NotificationCron extends TimedJob {
	public function __construct(
		protected ITimeFactory $time,
		private MailService $mailService,
		private ISession $session,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		parent::setInterval(5); // run every 5 minutes
	}

	/**
	 * @param mixed $argument
	 * @return void
	 */
	protected function run($argument) {
		$this->session->set(AppConstants::SESSION_KEY_CRON_JOB, true);
		try {
			$this->mailService->sendNotifications();
		} catch (Exception $e) {
			$this->logger->error(
				'NotificationCron: An error occurred while running the notification cron: {message}',
				['message' => $e->getMessage()]
			);
		} finally {
			$this->session->remove(AppConstants::SESSION_KEY_CRON_JOB);
		}

	}

	public function manuallyRun(): string {
		$this->logger->info('NotificationCron manually run.');
		$this->run(null);
		return 'NotificationCron manually run.';
	}
}
