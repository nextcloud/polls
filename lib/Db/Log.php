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
 * @method integer getPollId()
 * @method void setPollId(integer $value)
 * @method integer getCreated()
 * @method void setCreated(integer $value)
 * @method integer getUserId()
 * @method void setUserId(string $value)
 * @method string getDisplayName()
 * @method void setDisplayName(string $value)
 * @method string getMessageId()
 * @method void setMessageId(string $value)
 * @method string getMessage()
 * @method void setMessage(string $value)
 */
class Log extends Entity implements JsonSerializable {
	protected $created;
	protected $processed;
	protected $pollId;
	protected $userId;
	protected $displayName;
	protected $messageId;
	protected $message;

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'created' => $this->created,
			'processed' => $this->processed,
			'pollId' => $this->pollId,
			'userId' => $this->userId,
			'displayName' => $this->displayName,
			'message_id' => $this->messageId,
			'message' => $this->message
		];
	}
}
