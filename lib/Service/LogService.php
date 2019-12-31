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

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;

use OCA\Polls\Db\Log;
use OCA\Polls\Db\LogMapper;

class LogService {

	private $mapper;
	private $logItem;

	/**
	 * LogService constructor.
	 * @param LogMapper $mapper
	 * @param Log $logItem
	 */

	public function __construct(
		LogMapper $mapper,
		Log $logItem
	) {
		$this->mapper = $mapper;
		$this->logItem = $logItem;
	}


	/**
	 * Prevent repetition of the same log event
	 * @NoAdminRequired
	 * @return Bool
	 */
	public function isRepetition() {
		try {
			$lastRecord = $this->mapper->getLastRecord($this->logItem->getPollId());
			return (intval($lastRecord->getPollId()) === intval($this->logItem->getPollId())
				&& $lastRecord->getUserId() === $this->logItem->getUserId()
				&& $lastRecord->getMessageId() === $this->logItem->getMessageId()
				&& $lastRecord->getMessage() === $this->logItem->getMessage()
			);
		} catch (DoesNotExistException $e) {
			return false;
		}
	}

	/**
	 * Log poll activity
	 * @NoAdminRequired
	 * @param $pollId
	 * @param $messageId
	 * @param $userId
	 * @param $message
	 * @return Log
	 */
	public function setLog($pollId, $messageId, $userId = null, $message = null) {
		$this->logItem = new Log();
		$this->logItem->setPollId($pollId);
		$this->logItem->setCreated(time());
		$this->logItem->setMessageId($messageId);
		$this->logItem->setMessage($message);

		if ($userId) {
			$this->logItem->setUserId($userId);
		} else {
			$this->logItem->setUserId(\OC::$server->getUserSession()->getUser()->getUID());
		}


		if ($this->isRepetition()) {
			return null;
		} else {
			return $this->mapper->insert($this->logItem);
		}
	}

}
