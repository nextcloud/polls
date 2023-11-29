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

use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\WatchMapper;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class JanitorCron extends TimedJob {
	private AppSettings $appSettings;

	public function __construct(
		protected ITimeFactory $time,
		private LogMapper $logMapper,
		private PollMapper $pollMapper,
		private WatchMapper $watchMapper
	) {
		parent::__construct($time);
		parent::setInterval(86400); // run once a day
		$this->logMapper = $logMapper;
		$this->pollMapper = $pollMapper;
		$this->watchMapper = $watchMapper;
		$this->appSettings = new AppSettings;
	}

	/**
	 * @param mixed $argument
	 * @return void
	 */
	protected function run($argument) {
		$this->logMapper->deleteProcessedEntries(); // delete processed log entries
		$this->logMapper->deleteOldEntries(time() - (86400 * 7)); // delete entries older than 7 days
		$this->watchMapper->deleteOldEntries(time() - 86400); // delete entries older than 1 day

		if ($this->appSettings->getBooleanSetting(AppSettings::SETTING_AUTO_ARCHIVE) && $this->appSettings->getIntegerSetting(AppSettings::SETTING_AUTO_ARCHIVE_OFFSET) > 0) {
			$this->pollMapper->archiveExpiredPolls(
				time() - ($this->appSettings->getAutoarchiveOffset() * 86400)
			); // archive polls after defined days after closing date
		}
	}
}
