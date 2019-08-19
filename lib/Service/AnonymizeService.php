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
	 * Create a mapping list with unique Anonymous strings based on the partcipants of a poll
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return array
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
	 * @return array Returns the original array with anonymized user names
	 */
	public function getAnonymizedList($array, $pollId) {
		// get mapping for the complete poll
		$anonList = $this->anonMapper($pollId);
		foreach ($array as &$element) {
			// skip current user
			if ($element->getUserId() !== \OC::$server->getUserSession()->getUser()->getUID()) {
				// throw new \Exception( json_encode($element->getUserId()) );
				// Check, if searched user name is in mapping array
				if (isset($anonList[$element->getUserId()])) {
					//replace original user name
					$element->setUserId($anonList[$element->getUserId()]);
				} else {
					// User name is not in mapping array, set static text
					$element->setUserId('Unknown user');
				}
			}
		}

		return (object) $array;
	}
}
