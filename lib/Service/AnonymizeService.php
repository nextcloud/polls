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

use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;

class AnonymizeService {

	private $voteMapper;
	private $commentMapper;
	private $anonList = array();
	private $userId;
	private $pollId;

	public function __construct(
		VoteMapper $voteMapper,
		CommentMapper $commentMapper
	) {
		$this->voteMapper = $voteMapper;
		$this->commentMapper = $commentMapper;
	}

	/**
	 * Anonymizes the participants of a poll
	 * @NoAdminRequired
	 * @param Array $array Input list which should be anonymized must be a collection of Vote or Comment
	 * @return array Returns the original array with anonymized user names
	 */
	private function anonymize($array) {
		// get mapping for the complete poll
		foreach ($array as &$element) {
			// skip current user
			if ($element->getUserId() !== $this->userId) {
				// Check, if searched user name is in mapping array
				if (isset($this->anonList[$element->getUserId()])) {
					//replace original user name
					$element->setUserId($this->anonList[$element->getUserId()]);
				} else {
					// User name is not in mapping array, set static text
					$element->setUserId('Unknown user');
				}
			}
		}

		return $array;
	}

	/**
	 * Initialize anonymizer with pollId and userId
	 * Creates a mapping list with unique Anonymous strings based on the partcipants of a poll
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @param string $userId - usernames, which will not be anonymized
	 */

	public function set($pollId, $userId) {
		$this->pollId = $pollId;
		$this->userId = $userId;
		$votes = $this->voteMapper->findByPoll($pollId);
		$comments = $this->commentMapper->findByPoll($pollId);
		$i = 0;

		foreach ($votes as $element) {
			if (!array_key_exists($element->getUserId(), $this->anonList) && $element->getUserId() !== $userId) {
				$this->anonList[$element->getUserId()] = 'Anonymous ' . ++$i;
			}
		}

		foreach ($comments as $element) {
			if (!array_key_exists($element->getUserId(), $this->anonList) && $element->getUserId() !== $userId) {
				$this->anonList[$element->getUserId()] = 'Anonymous ' . ++$i;
			}
		}
		return;
	}

	/**
	 * Anonymizes the comments of a poll
	 * @NoAdminRequired
	 * @return object Returns anonymized comments
	 */
	public function getComments() {
		// get mapping for the complete poll
		return (object) $this->anonymize($this->commentMapper->findByPoll($this->pollId));
	}

	/**
	 * Anonymizes the participants of a poll
	 * @NoAdminRequired
	 * @return array Returns anonymized votes
	 */
	public function getVotes() {
		return (object) $this->anonymize($this->voteMapper->findByPoll($this->pollId));
	}


}
