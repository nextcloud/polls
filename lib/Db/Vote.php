<?php

declare(strict_types=1);
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

/**
 * @method int getId()
 * @method void setId(integer $value)
 * @method int getPollId()
 * @method void setPollId(integer $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method int getVoteOptionId()
 * @method void setVoteOptionId(integer $value)
 * @method string getVoteOptionText()
 * @method void setVoteOptionText(string $value)
 * @method string getVoteOptionHash()
 * @method void setVoteOptionHash(string $value)
 * @method string getVoteAnswer()
 * @method void setVoteAnswer(string $value)
 */
class Vote extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_votes';
	public const VOTE_YES = 'yes';
	public const VOTE_NO = 'no';
	public const VOTE_EVENTUALLY = 'maybe';

	public $id = null;
	protected int $pollId = 0;
	protected string $userId = '';
	protected int $voteOptionId = 0;
	protected string $voteOptionText = '';
	protected string $voteOptionHash = '';
	protected string $voteAnswer = '';

	public function __construct() {
		$this->addType('id', 'int');
		$this->addType('pollId', 'int');
		$this->addType('voteOptionId', 'int');
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'pollId' => $this->getPollId(),
			'optionText' => $this->getVoteOptionText(),
			'answer' => $this->getVoteAnswer(),
			'user' => $this->getUser(),
		];
	}
}
