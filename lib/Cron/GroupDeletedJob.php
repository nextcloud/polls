<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Cron;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

use Psr\Log\LoggerInterface;

class GroupDeletedJob extends QueuedJob {
	/**
	 * @psalm-api
	 */
	public function __construct(
		private ShareMapper $shareMapper,
		protected ITimeFactory $time,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
	}

	/**
	 * @param mixed $argument
	 * @return void
	 */
	protected function run($argument) {
		$group = $argument['group'];
		$this->logger->info('Removing group shares for deleted group {group}', [
			'group' => $group
		]);

		$this->shareMapper->deleteByIdAndType($group, Share::TYPE_GROUP);
	}
}
