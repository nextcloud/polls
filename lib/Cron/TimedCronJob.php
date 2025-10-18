<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Cron;

use Exception;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * @psalm-api
 */
abstract class TimedCronJob extends TimedJob {
	public function __construct(
		protected ITimeFactory $time,
		protected LoggerInterface $logger,
		protected bool $supportsManualRun = false,
	) {
		parent::__construct($time);
		parent::setInterval(86400); // default run once a day
	}

	/**
	 * Manually run the cron job is disabled by default
	 * You can override this method in your cron job class to enable it
	 *
	 * @return string
	 */
	public function manuallyRun(): string {
		$reflection_class = new \ReflectionClass($this);
		$jobClassName = $reflection_class->getName();

		$this->logger->error('{job} does not support manual execution', ['job' => $jobClassName]);
		throw new Exception('Job does not support manual execution');
	}

}
