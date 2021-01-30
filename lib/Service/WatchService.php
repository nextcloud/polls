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

use OCP\AppFramework\Db\DoesNotExistException;

use OCA\Polls\Db\Watch;
use OCA\Polls\Db\WatchMapper;

class WatchService {

	/** @var WatchMapper */
	private $watchMapper;

	/** @var Watch */
	private $watch;

	public function __construct(
		WatchMapper $watchMapper
	) {
		$this->watchMapper = $watchMapper;
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
		try {
			$this->watch = $this->watchMapper->findForPollIdAndTable($pollId, $table);
		} catch (DoesNotExistException $e) {
			$this->watch = new Watch();
			$this->watch->setPollId($pollId);
			$this->watch->setTable($table);
			$this->watch = $this->watchMapper->insert($this->watch);
		}

		$this->watch->setUpdated(time());
		return $this->watchMapper->update($this->watch);
	}
}
