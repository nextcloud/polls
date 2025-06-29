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
use OCA\Polls\Db\SubscriptionMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\WatchMapper;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\ISession;
use Psr\Log\LoggerInterface;

/**
 * @psalm-api
 */
class JanitorCron extends TimedJob {
	private AppSettings $appSettings;

	public function __construct(
		protected ITimeFactory $time,
		private CommentMapper $commentMapper,
		private ISession $session,
		private LoggerInterface $logger,
		private LogMapper $logMapper,
		private OptionMapper $optionMapper,
		private PollMapper $pollMapper,
		private ShareMapper $shareMapper,
		private SubscriptionMapper $subscriptionMapper,
		private VoteMapper $voteMapper,
		private WatchMapper $watchMapper,
	) {
		parent::__construct($time);
		parent::setInterval(86400); // run once a day
		$this->appSettings = Container::queryClass(AppSettings::class);
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

		// delete orphaned entries (poll_id = null)
		$this->commentMapper->deleteOrphaned();
		$this->logMapper->deleteOrphaned();
		$this->optionMapper->deleteOrphaned();
		$this->shareMapper->deleteOrphaned();
		$this->subscriptionMapper->deleteOrphaned();
		$this->voteMapper->deleteOrphaned();

		$autoArchiveOffset = $this->appSettings->getAutoArchiveOffsetDays();

		// archive polls after defined days after closing date
		if ($this->appSettings->getAutoArchiveEnabled() && $autoArchiveOffset > 0) {
			$affectedRows = $this->pollMapper->archiveExpiredPolls(
				time() - ($autoArchiveOffset * 86400)
			);
			if ($affectedRows > 0) {
				$this->logger->info(
					'JanitorCron: Archived {count} poll(s).',
					['count' => $affectedRows]
				);
			}
		}

		$autoDeleteOffset = $this->appSettings->getAutoDeleteOffsetDays();
		// delete polls after defined days after archiving date
		if ($this->appSettings->getAutoDeleteEnabled() && $autoDeleteOffset > 0) {
			$affectedRows = $this->pollMapper->deleteArchivedPolls(
				time() - ($autoDeleteOffset * 86400)
			);
			if ($affectedRows > 0) {
				$this->logger->info(
					'JanitorCron: Deleted {count} archived poll(s).',
					['count' => $affectedRows]
				);
			}
		}

		$this->session->remove(AppConstants::SESSION_KEY_CRON_JOB);
	}

	public function manuallyRun(): string {
		$this->run(null);
		return 'JanitorCron manually run.';
	}

}
