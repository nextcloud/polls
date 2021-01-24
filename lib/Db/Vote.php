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
 * @method int getPollId()
 * @method void setPollId(integer $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method int getVoteOptionId()
 * @method void setVoteOptionId(integer $value)
 * @method string getVoteOptionText()
 * @method void setVoteOptionText(string $value)
 * @method string getVoteAnswer()
 * @method void setVoteAnswer(string $value)
 */
class Vote extends Entity implements JsonSerializable {

	/** @var int $pollId */
	protected $pollId;

	/** @var string $userId */
	protected $userId;

	/** @var int $voteOptionId */
	protected $voteOptionId;

	/** @var string $voteOptionText */
	protected $voteOptionText;

	/** @var string $voteAnswer */
	protected $voteAnswer;

	public function jsonSerialize() {
		return [
			'id' => intval($this->id),
			'pollId' => intval($this->pollId),
			'userId' => $this->userId,
			'voteOptionId' => intval($this->voteOptionId),
			'voteOptionText' => $this->voteOptionText,
			'voteAnswer' => $this->voteAnswer,
			'isNoUser' => $this->getIsNoUser(),
			'displayName' => $this->getDisplayName()
		];
	}

	public function getDisplayName(): string {
		return $this->getIsNoUser()
			? $this->userId
			: \OC::$server->getUserManager()->get($this->userId)->getDisplayName();
	}

	public function getIsNoUser(): bool {
		return !(\OC::$server->getUserManager()->get($this->userId) instanceof IUser);
	}
}
