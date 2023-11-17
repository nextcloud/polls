<?php

declare(strict_types=1);
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

use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\VoteMapper;

class AnonymizeService {
	private array $anonList;
	private ?string $userId;

	public function __construct(
		private CommentMapper $commentMapper,
		private OptionMapper $optionMapper,
		private VoteMapper $voteMapper,
	) {
		$this->anonList = [];
		$this->userId = null;
	}

	/**
	 * Anonymizes the participants of a poll
	 * $array Input list which should be anonymized must be a collection of Vote or Comment
	 * Returns the original array with anonymized user names
	 */
	public function anonymize(array &$array): void {
		// get mapping for the complete poll
		foreach ($array as &$element) {
			if (!$element->getUserId() || $element->getUserId() === $this->userId) {
				// skip current user
				continue;
			}
			$element->setUserId($this->anonList[$element->getUserId()] ?? 'Unknown user');
		}
	}

	/**
	 * Initialize anonymizer with pollId and userId
	 * Creates a mapping list with unique Anonymous strings based on the partcipants of a poll
	 * $userId - usernames, which will not be anonymized
	 */
	public function set(int $pollId, string $userId): void {
		$this->userId = $userId;
		$votes = $this->voteMapper->findByPoll($pollId);
		$comments = $this->commentMapper->findByPoll($pollId);
		$options = $this->optionMapper->findByPoll($pollId);
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
		foreach ($options as $element) {
			if (!array_key_exists($element->getUserId(), $this->anonList) && $element->getUserId() !== $userId) {
				$this->anonList[$element->getUserId()] = 'Anonymous ' . ++$i;
			}
		}
		return;
	}
}
