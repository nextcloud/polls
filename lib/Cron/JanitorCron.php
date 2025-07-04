<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Cron;

use Exception;
use OCA\Polls\AppConstants;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\TableManager;
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
		private WatchMapper $watchMapper,
		private TableManager $tableManager,
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

		try {

			// delete processed log entries
			$this->logMapper->deleteProcessedEntries();

			// delete entries older than 7 days
			$this->logMapper->deleteOldEntries(time() - (86400 * 7));

			// delete entries older than 1 day
			$this->watchMapper->deleteOldEntries(time() - 86400);

			// purge entries virtually deleted more than 12 hour ago
			$deleted['comments'] = $this->commentMapper->purgeDeletedComments(time() - 4320);
			$deleted['options'] = $this->optionMapper->purgeDeletedOptions(time() - 4320);
			$deleted['shares'] = $this->shareMapper->purgeDeletedShares(time() - 4320);

			// delete polls after defined days after archiving date
			$autoDeleteOffset = $this->appSettings->getAutoDeleteOffsetDays();
			if ($this->appSettings->getAutoDeleteEnabled() && $autoDeleteOffset > 0) {
				$deleted['archived poll'] = $this->pollMapper->deleteArchivedPolls(
					time() - ($autoDeleteOffset * 86400)
				);
			}

			foreach ($deleted as $type => $count) {
				if ($count > 0) {
					$this->logger->info(
						'JanitorCron: Purged {count} {type}(s).',
						['count' => $count, 'type' => $type]
					);
				}
			}

			// delete orphaned entries (poll_id = null)
			$orphaned = $this->tableManager->removeOrphaned();
			foreach ($orphaned as $type => $count) {
				if ($count > 0) {
					$this->logger->info(
						'JanitorCron: Purged {count} orphaned record(s) from {type}.',
						['count' => $count, 'type' => $type]
					);
				}
			}


			// archive polls after defined days after closing date
			$autoArchiveOffset = $this->appSettings->getAutoArchiveOffsetDays();

			if ($this->appSettings->getAutoArchiveEnabled() && $autoArchiveOffset > 0) {
				$archived['poll'] = $this->pollMapper->archiveExpiredPolls(
					time() - ($autoArchiveOffset * 86400)
				);

				foreach ($archived as $type => $count) {
					if ($count > 0) {
						$this->logger->info(
							'JanitorCron: Archived {count} poll(s).',
							['count' => $count, 'type' => $type]
						);
					}
				}
			}
		} catch (Exception $e) {
			$this->logger->error(
				'JanitorCron: An error occurred while running the janitor cron: {message}',
				['message' => $e->getMessage()]
			);
		} finally {
			$this->session->remove(AppConstants::SESSION_KEY_CRON_JOB);
		}
	}

	public function manuallyRun(): string {
		$this->logger->info('JanitorCron manually run.');
		$this->run(null);
		return 'JanitorCron manually run.';
	}

}
