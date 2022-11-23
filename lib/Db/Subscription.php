<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 * @author Kai Schröer <git@schroeer.co>
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

namespace OCA\Polls\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int getId()
 * @method void setId(integer $value)
 * @method int getPollId()
 * @method void setPollId(integer $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 */
class Subscription extends Entity implements JsonSerializable {
	public const TABLE = 'polls_notif';

	/** @var int $pollId */
	protected $pollId = 0;

	/** @var string $userId */
	protected $userId = '';

	/** @var Log[] $logEntries */
	protected $logEntries = [];

	public function __construct() {
		$this->addType('pollId', 'int');
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'pollId' => $this->getPollId(),
			'userId' => $this->getUserId(),
		];
	}

	/**
	 * @param Log[] $logs Array of logs for notifications
	 */
	public function setNotifyLogs(array $logs) : void {
		$pollId = $this->getPollId();
		$this->logEntries = array_filter($logs, function ($log) use ($pollId) {
			return $log->getPollId() === $pollId;
		});
	}

	/**
	 * @return Log[]
	 */
	public function getNotifyLogs() : array {
		return $this->logEntries;
	}
}
