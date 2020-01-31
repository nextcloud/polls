<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 * @author Kai Schröer <git@schroeer.co>
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

namespace OCA\Polls\Db;

use JsonSerializable;

use OCP\IUser;
use OCP\AppFramework\Db\Entity;

/**
 * @method integer getId()
 * @method void setId(integer $value)
 * @method integer getPollId()
 * @method void setPollId(integer $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method string getDt()
 * @method void setDt(string $value)
 * @method string getComment()
 * @method void setComment(string $value)
 */
class Comment extends Entity implements JsonSerializable {

	/** @var int $pollId */
	protected $pollId;

	/** @var string $userId */
	protected $userId;

	/** @var string $dt */
	protected $dt;

	/** @var int $timestamp */
	protected $timestamp;

	/** @var string $comment */
	protected $comment;

	public function jsonSerialize() {

		// too lazy for a migration
		// use timestamp if is set,
		// otherwise use dt and convert to timestamp

		if (intval($this->timestamp) > 0) {
			$timestamp = $this->timestamp;
		} else {
			$timestamp = strtotime($this->dt);
		}

		return [
			'id' => intval($this->id),
			'pollId' => intval($this->pollId),
			'userId' => $this->userId,
			'dt' => $this->dt,
			'timestamp' => intval($timestamp),
			'comment' => $this->comment,
			'displayName' => $this->getDisplayName()
		];
	}

	private function getDisplayName() {

		if (\OC::$server->getUserManager()->get($this->userId) instanceof IUser) {
			return \OC::$server->getUserManager()->get($this->userId)->getDisplayName();
		} else {
			return $this->userId;
		}
	}
}
