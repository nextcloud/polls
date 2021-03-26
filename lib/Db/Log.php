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
 * @method int getPollId()
 * @method void setPollId(integer $value)
 * @method int getCreated()
 * @method void setCreated(integer $value)
 * @method int getProcessed()
 * @method void setProcessed(integer $value)
 * @method int getUserId()
 * @method void setUserId(string $value)
 * @method string getDisplayName()
 * @method void setDisplayName(string $value)
 * @method string getMessageId()
 * @method void setMessageId(string $value)
 */
class Log extends Entity implements JsonSerializable {
	public const MSG_ID_ADDPOLL = 'addPoll';
	public const MSG_ID_UPDATEPOLL = 'updatePoll';
	public const MSG_ID_DELETEPOLL = 'deletePoll';
	public const MSG_ID_RESTOREPOLL = 'restorePoll';
	public const MSG_ID_EXPIREPOLL = 'expirePoll';
	public const MSG_ID_ADDOPTION = 'addOption';
	public const MSG_ID_DELETEOPTION = 'deleteOption';
	public const MSG_ID_SETVOTE = 'setVote';
	public const MSG_ID_OWNERCHANGE = 'updateOwner';

	/** @var int $pollId */
	protected $pollId;

	/** @var int $created */
	protected $created;

	/** @var int $processed */
	protected $processed;

	/** @var string $userId */
	protected $userId;

	/** @var string $displayName */
	protected $displayName;

	/** @var string $messageId */
	protected $messageId;

	public function __construct() {
		$this->addType('pollId', 'integer');
		$this->addType('created', 'integer');
		$this->addType('processed', 'integer');
	}

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'pollId' => $this->pollId,
			'created' => $this->created,
			'processed' => $this->processed,
			'userId' => $this->userId,
			'displayName' => $this->displayName,
			'message_id' => $this->messageId
		];
	}
}
