<?php
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

use OCA\Polls\Db\Watch;
use OCA\Polls\Db\WatchMapper;
use OCA\Polls\Exceptions\NoUpdatesException;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\ISession;

class WatchService {
	/** @var AppSettings */
	private $appSettings;

	/** @var ISession */
	protected $session;

	/** @var WatchMapper */
	private $watchMapper;

	/** @var Watch */
	private $watch;

	public function __construct(
		ISession $session,
		WatchMapper $watchMapper
	) {
		$this->appSettings = new AppSettings;
		$this->session = $session;
		$this->watch = new Watch;
		$this->watchMapper = $watchMapper;
	}

	/**
	 * Watch poll for updates
	 */
	public function watchUpdates(int $pollId, ?int $offset): array {
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
	public function getUpdates(int $pollId, int $offset): array {
		try {
			return $this->watchMapper->findUpdatesForPollId($pollId, $offset);
		} catch (DoesNotExistException $e) {
			return [];
		}
	}

	/**
	 * @return Watch
	 */
	public function writeUpdate(int $pollId, string $table): Watch {
		$sessionId = hash('md5', $this->session->get('ncPollsClientId'));
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
		return $this->watchMapper->update($this->watch);
	}
}
