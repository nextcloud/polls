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

use OCA\Polls\Db\VoteMapper;

class AnonymizeService {

	private $mapper;

	public function __construct(
		VoteMapper $mapper
	) {
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
	 * @param Array $array Input list which schould be anonymized
	 * @param String $anomizeField - The field, which carries the user name, which is to anonymize
	 * @return Array Returns the original array with anonymized user names
	 */
	public function getAnonymizedList($array, $pollId, $anomizeField = 'userId') {
		// get mapping for the complete poll
		$anonList = $this->anonMapper($pollId);
		// initialize counter
		$i = 0;


		for ($i = 0; $i < count($array); ++$i) {
			// skip current user
			if ($array[$i][$anomizeField] !== \OC::$server->getUserSession()->getUser()->getUID()) {
				// Check, if searched user name is in mapping array
				if (isset($anonList[$array[$i][$anomizeField]])) {
					//replace original user name
					$array[$i][$anomizeField] =  $anonList[$array[$i][$anomizeField]];
				} else {
					// User name is not in mapping array, set static text
					$array[$i][$anomizeField] = 'Unknown user';
				}
			}
		}

		return $array;
	}
}
