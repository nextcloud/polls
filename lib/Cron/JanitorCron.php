<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Cron;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\WatchMapper;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\ISession;
use OCP\Server;

/**
 * @psalm-api
 */
class JanitorCron extends TimedJob {
	private AppSettings $appSettings;

	public function __construct(
		protected ITimeFactory $time,
		private LogMapper $logMapper,
		private PollMapper $pollMapper,
		private WatchMapper $watchMapper,
		private CommentMapper $commentMapper,
		private OptionMapper $optionMapper,
		private ShareMapper $shareMapper,
		private ISession $session,
	) {
		parent::__construct($time);
		parent::setInterval(86400); // run once a day
		$this->appSettings = Server::get(AppSettings::class);
	}

	/**
	 * @param mixed $argument
	 * @return void
	 */
	protected function run($argument) {
		$this->session->set(AppConstants::SESSION_KEY_CRON_JOB, true);
		// delete processed log entries
		$this->logMapper->deleteProcessedEntries();

		// delete entries older than 7 days
		$this->logMapper->deleteOldEntries(time() - (86400 * 7));

		// delete entries older than 1 day
		$this->watchMapper->deleteOldEntries(time() - 86400);

		// purge entries virtually deleted more than 12 hour ago
		$this->commentMapper->purgeDeletedComments(time() - 4320);
		$this->optionMapper->purgeDeletedOptions(time() - 4320);
		$this->shareMapper->purgeDeletedShares(time() - 4320);
		$autoArchiveOffest = $this->appSettings->getAutoarchiveOffsetDays();
		// archive polls after defined days after closing date
		if ($this->appSettings->getAutoarchiveEnabled() && $autoArchiveOffest > 0) {
			$this->pollMapper->archiveExpiredPolls(
				time() - ($autoArchiveOffest * 86400)
			);
		}
		$this->session->remove(AppConstants::SESSION_KEY_CRON_JOB);
	}

	public function manuallyRun(): string {
		$this->run(null);
		return 'JanitorCron manually run.';
	}

}
