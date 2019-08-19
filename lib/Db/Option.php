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

use OCP\AppFramework\Db\Entity;

/**
 * @method integer getPollId()
 * @method void setPollId(integer $value)
 * @method string getPollOptionText()
 * @method void setPollOptionText(string $value)
 * @method integer getTimestamp()
 * @method void setTimestamp(integer $value)
 */
class Option extends Entity implements JsonSerializable {
	protected $pollId;
	protected $pollOptionText;
	protected $timestamp;

	public function jsonSerialize() {

		return [
			'id' => $this->id,
			'pollId' => $this->pollId,
			'pollOptionText' => $this->pollOptionText,
			'timestamp' => $this->timestamp
		];
	}
	//
	// /**
	//  * Option constructor.
	//  */
	// public function __construct() {
	// 	$this->addType('pollId', 'integer');
	// 	$this->addType('timestamp', 'integer');
	// }
	//
	// /**
	//  * Make shure, timestamp and Text are filled correctly
	//  * @NoAdminRequired
	//  * @deprecated 1.0
	//  * @return Timestamp
	//  */
	// private function getTimestampTemp() {
	// 	if ($this->getTimestamp() > 0) {
	// 		return $this->getTimestamp();
	// 	} else if (strtotime($this->getPollOptionText())) {
	// 		return strtotime($this->getPollOptionText());
	// 	} else {
	// 		return 0;
	// 	}
	// }
	//
	// /**
	//  * Return Option object with all properties
	//  * @NoAdminRequired
	//  * @deprecated 1.0 Moved to OptionController
	//  * @return array
	//  */
	// public function read() {
	// 	return [
	// 		'id' => $this->getId(),
	// 		'pollId' => $this->getPollId(),
	// 		'text' => htmlspecialchars_decode($this->getPollOptionText()),
	// 		'timestamp' => $this->getTimestampTemp()
	// 	];
	// }
}
