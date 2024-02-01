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

use OCA\Polls\Db\Log;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\Db\UserMapper;

class LogService {
	public function __construct(
		private LogMapper $logMapper,
		private Log $log,
		private UserMapper $userMapper,
	) {
	}

	/**
	 * Log poll activity
	 */
	public function setLog(int $pollId, string $messageId, ?string $userId = null): ?Log {
		$this->log = new Log();
		$this->log->setPollId($pollId);
		$this->log->setCreated(time());
		$this->log->setMessageId($messageId);
		$this->log->setUserId($userId ?? $this->userMapper->getCurrentUser()->getId());

		return $this->logMapper->insert($this->log);
	}
}
