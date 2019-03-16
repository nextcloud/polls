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

use OCP\AppFramework\Db\Entity;

/**
 * @method integer getPollId()
 * @method void setPollId(integer $value)
 * @method string getPollOptionText()
 * @method void setPollOptionText(string $value)
 * @method integer getTimestamp()
 * @method void setTimestamp(integer $value)
 */
class Option extends Model {
	protected $pollId;
	protected $pollOptionText;
	protected $timestamp;

	/**
	 * Option constructor.
	 */
	public function __construct() {
		$this->addType('pollId', 'integer');
		$this->addType('timestamp', 'integer');
	}

	public function read() {
		return [
			'id' => $this->getId(),
			'text' => htmlspecialchars_decode($this->getPollOptionText()),
			'timestamp' => $this->getTimestamp()
		];
	}
}
