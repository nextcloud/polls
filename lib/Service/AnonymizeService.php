<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
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


namespace OCA\Polls\Service;

use Exception;

use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;

class AnonymizeService {

	private $mapper;
	// private $userId;
	//
	// private $pollId;
	// private $anomizeField;

	public function __construct(VoteMapper $mapper) {
		$this->mapper = $mapper;
	}

	/**
	 * Create a mapping list based on the partcipants of a poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return Array
	 */
	private function anonMapper($pollId) {
		$anonList = array();
		$votes = $this->mapper->findByPoll($pollId);
		$i = 0;

		foreach ($votes as $element) {
			if (!array_key_exists($element->getUserId(), $anonList)) {
				$anonList[$element->getUserId()] = 'Anonymous ' . ++$i;
			}
		}

		return $anonList;
	}

	/**
	 * Anonymizes the participants of a poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Array $array
	 * @param String $anomizeField
	 * @return Array
	 */
	public function getAnonymizedList($array, $pollId, $anomizeField = 'userId') {
		$anonList = $this->anonMapper($pollId);
		$currentUser = \OC::$server->getUserSession()->getUser()->getUID();
		$i = 0;

		for ($i = 0; $i < count($array); ++$i) {
			if ($array[$i][$anomizeField] !== $currentUser) {
				$array[$i][$anomizeField] = $anonList[$array[$i][$anomizeField]];
			}
		}

		return $array;
	}


}
