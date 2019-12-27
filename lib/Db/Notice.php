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
 * @method integer getChannel()
 * @method void setChannel(integer $value)
 * @method integer getUserId()
 * @method void setUserId(integer $value)
 * @method integer getUserEmail()
 * @method void setUserEmail(integer $value)
 * @method integer getDisplayName()
 * @method void setDisplayName(integer $value)
 * @method integer getMessageId()
 * @method void setMessageId(integer $value)
 * @method string getMessage()
 * @method void setMessage(string $value)
 */
class Notice extends Entity implements JsonSerializable {
	protected $pollId;
	protected $channel;
	protected $userId;
	protected $userEmail;
	protected $displayName;
	protected $messageId;
	protected $message;

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'pollId' => $this->pollId,
			'channel' => $this->channel,
			'userId' => $this->userId,
			'userEmail' => $this->userEmail,
			'displayName' => $this->displayName,
			'message_id' => $this->messageId,
			'message' => $this->message
		];
	}
}
