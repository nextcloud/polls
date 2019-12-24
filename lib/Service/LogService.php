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

use OCA\Polls\Db\Log;
use OCA\Polls\Db\LogMapper;

class LogService  {

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
	* Log poll events
	* @NoAdminRequired
	* @param $pollId related pollId
	* @param $messageId identifier for message notice
	* @param $userId (optional)
	* @param $message message text if $messageId is === 'custom'
	*/
	public function setLog($pollId, $messageId, $userId = null, $message = null) {
		$logItem = new Log();
		$logItem->setPollId($pollId);
		$logItem->setCreated(time());
		$logItem->setMessageId($messageId);

		if ($userId) {
			$logItem->setUserId($userId);
		} else {
			$logItem->setUserId(\OC::$server->getUserSession()->getUser()->getUID());
		}

		$this->logger->alert(time());

		if ($messageId === 'custom') {
			$logItem->setMessage($message) ;
		} elseif (
			   $messageId !== 'deletePoll'
			&& $messageId !== 'restorePoll'
			&& $this->mapper->isSameLogWrittenRecently($pollId, $messageId)
		) {
			// do not log same log entry for 5 minutes
			// except delete and restore poll
			return;
		}
		$this->mapper->insert($logItem);
	}

}
