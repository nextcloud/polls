<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author René Gieling <githung@dartcafe.de>
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
 * @method string getTable()
 * @method void setTable(string $value)
 * @method int getUpdated()
 * @method void setUpdated(integer $value)
 */
class Watch extends Entity implements JsonSerializable {
	public const OBJECT_POLLS = "polls";
	public const OBJECT_VOTES = "votes";
	public const OBJECT_OPTIONS = "options";
	public const OBJECT_COMMENTS = "comments";
	public const OBJECT_SHARES = "shares";

	/** @var int $pollId */
	protected $pollId;

	/** @var string $tableId */
	protected $table;

	/** @var integer $updated */
	protected $updated;

	public function __construct() {
		$this->addType('pollId', 'int');
		$this->addType('updated', 'int');
	}

	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'pollId' => $this->getPollId(),
			'table' => $this->getTable(),
			'updated' => $this->getUpdated(),
		];
	}
}
