<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Cron;

use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\WatchMapper;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
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

		// archive polls after defined days after closing date
		if ($this->appSettings->getBooleanSetting(AppSettings::SETTING_AUTO_ARCHIVE) && $this->appSettings->getIntegerSetting(AppSettings::SETTING_AUTO_ARCHIVE_OFFSET) > 0) {
			$this->pollMapper->archiveExpiredPolls(
				time() - ($this->appSettings->getAutoarchiveOffset() * 86400)
			);
		}
	}
	public function manuallyRun(): string {
		$this->run(null);
		return 'JanitorCron manually run.';
	}

}
