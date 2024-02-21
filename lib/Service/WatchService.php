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

namespace OCA\Polls\Service;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\Watch;
use OCA\Polls\Db\WatchMapper;
use OCA\Polls\Exceptions\NoUpdatesException;
use OCA\Polls\Model\Acl;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\ISession;

class WatchService {
	private AppSettings $appSettings;
	private Watch $watch;

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private ISession $session,
		private WatchMapper $watchMapper,
		private Acl $acl,
	) {
		$this->appSettings = new AppSettings;
		$this->watch = new Watch;
	}

	/**
	 * Watch poll for updates
	 */
	public function watchUpdates(int $pollId = 0, ?int $offset = null): array {
		if ($pollId !== 0) {
			$this->acl->setPollId($pollId);
		}

		$start = time();
		$timeout = 30;
		$offset = $offset ?? $start;

		if ($this->appSettings->getUpdateType() === AppSettings::SETTING_UPDATE_TYPE_LONG_POLLING) {
			while (empty($updates) && time() <= $start + $timeout) {
				sleep(1);
				$updates = $this->getUpdates($pollId, $offset);
			}
		} else {
			$updates = $this->getUpdates($pollId, $offset);
		}

		if (empty($updates)) {
			throw new NoUpdatesException;
		}

		return $updates;
	}

	/**
	 * @return Watch[]
	 */
	private function getUpdates(int $pollId, int $offset): array {
		try {
			return $this->watchMapper->findUpdatesForPollId($pollId, $offset);
		} catch (DoesNotExistException $e) {
			return [];
		}
	}

	public function writeUpdate(int $pollId, string $table): void {
		$sessionId = hash('md5', $this->session->get(AppConstants::CLIENT_ID));
		$this->watch = new Watch();
		$this->watch->setPollId($pollId);
		$this->watch->setTable($table);
		$this->watch->setSessionId($sessionId);

		try {
			$this->watch = $this->watchMapper->insert($this->watch);
		} catch (Exception $e) {
			if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $e;
			}
			$this->watch = $this->watchMapper->findForPollIdAndTable($pollId, $table);
		}

		$this->watch->setUpdated(time());
		$this->watchMapper->update($this->watch);
	}
}
