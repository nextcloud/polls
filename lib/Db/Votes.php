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

/**
 * @method integer getPollId()
 * @method void setPollId(integer $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method string getVoteDate()
 * @method void setVoteDate(string $value)
 * @method string getVoteText()
 * @method void setVoteText(string $value)
 * @method integer getOptionId()
 * @method void setOptionId(integer $value)
 * @method integer getOptionText()
 * @method void setOptionText(string $value)
 * @method integer getVoteType()
 * @method void setVoteType(integer $value)
 * @method integer getVoteAnswer()
 * @method void setVoteAnswer(string $value)
 */
class Votes extends Model {
	protected $pollId;
	protected $userId;
	protected $voteDate;
	protected $voteText;
	protected $voteOptionId;
	protected $voteOptionText;
	protected $voteType;
	protected $voteAnswer;

	/**
	 * Participation constructor.
	 */
	public function __construct() {
		$this->addType('pollId', 'integer');
	}
}
