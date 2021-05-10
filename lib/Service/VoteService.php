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

use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\VoteLimitExceededException;

use OCA\Polls\Db\Log;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\Watch;
use OCA\Polls\Model\Acl;

class VoteService {

	/** @var Acl */
	private $acl;

	/** @var AnonymizeService */
	private $anonymizer;

	/** @var LogService */
	private $logService;

	/** @var OptionMapper */
	private $optionMapper;

	/** @var PollMapper */
	private $pollMapper;

	/** @var Vote */
	private $vote;

	/** @var VoteMapper */
	private $voteMapper;

	/** @var WatchService */
	private $watchService;


	public function __construct(
		Acl $acl,
		AnonymizeService $anonymizer,
		LogService $logService,
		OptionMapper $optionMapper,
		PollMapper $pollMapper,
		Vote $vote,
		VoteMapper $voteMapper,
		WatchService $watchService
	) {
		$this->acl = $acl;
		$this->anonymizer = $anonymizer;
		$this->logService = $logService;
		$this->optionMapper = $optionMapper;
		$this->pollMapper = $pollMapper;
		$this->vote = $vote;
		$this->voteMapper = $voteMapper;
		$this->watchService = $watchService;
	}

	/**
	 * Read all votes of a poll based on the poll id and return list as array
	 */
	public function list(int $pollId = 0, string $token = ''): array {
		if ($token) {
			$this->acl->setToken($token);
		} else {
			$this->acl->setPollId($pollId);
		}

		try {
			if (!$this->acl->isAllowed(Acl::PERMISSION_POLL_RESULTS_VIEW)) {
				return $this->voteMapper->findByPollAndUser($this->acl->getpollId(), $this->acl->getUserId());
			} elseif (!$this->acl->isAllowed(Acl::PERMISSION_POLL_USERNAMES_VIEW)) {
				$this->anonymizer->set($this->acl->getpollId(), $this->acl->getUserId());
				return $this->anonymizer->getVotes();
			} else {
				return $this->voteMapper->findByPoll($this->acl->getpollId());
			}
		} catch (DoesNotExistException $e) {
			return [];
		}
	}

	private function checkLimits(int $optionId, string $userId):void {
		$option = $this->optionMapper->find($optionId);
		$poll = $this->pollMapper->find($option->getPollId());

		// check, if the optionlimit is reached or exceeded, if one is set
		if ($poll->getOptionLimit() > 0) {
			if ($poll->getOptionLimit() <= count($this->voteMapper->getYesVotesByOption($option->getPollId(), $option->getPollOptionText()))) {
				throw new VoteLimitExceededException;
			}
		}

		// check if the votelimit for the user is reached or exceeded, if one is set
		if ($poll->getVoteLimit() > 0) {
			$pollOptionTexts = [];
			$votecount = 0;

			$options = $this->optionMapper->findByPoll($option->getPollId());
			$votes = $this->voteMapper->getYesVotesByParticipant($option->getPollId(), $userId);

			// Only count votes, which match to an actual existing option.
			// Explanation: If an option is deleted, the corresponding votes are not deleted.

			// create an array of pollOptionTexts
			foreach ($options as $element) {
				$pollOptionTexts[] = $element->getPollOptionText();
			}

			// only count relevant votes for the limit
			foreach ($votes as $vote) {
				if (in_array($vote->getVoteOptionText(), $pollOptionTexts)) {
					$votecount++;
				}
			}
			if ($poll->getVoteLimit() <= $votecount) {
				throw new VoteLimitExceededException;
			}
		}
	}

	/**
	 * Set vote
	 */
	public function set(int $optionId, string $setTo, string $token = ''): Vote {
		$option = $this->optionMapper->find($optionId);

		if ($token) {
			$this->acl->setToken($token)->request(Acl::PERMISSION_VOTE_EDIT);
			if (intval($option->getPollId()) !== $this->acl->getPollId()) {
				throw new NotAuthorizedException;
			}
		} else {
			$this->acl->setPollId($option->getPollId())->request(Acl::PERMISSION_VOTE_EDIT);
		}

		if ($setTo === 'yes') {
			$this->checkLimits($optionId, $this->acl->getUserId());
		}

		try {
			$this->vote = $this->voteMapper->findSingleVote($this->acl->getPollId(), $option->getPollOptionText(), $this->acl->getUserId());
			$this->vote->setVoteAnswer($setTo);
			$this->voteMapper->update($this->vote);
		} catch (DoesNotExistException $e) {
			// Vote does not exist, insert as new Vote
			$this->vote = new Vote();

			$this->vote->setPollId($this->acl->getPollId());
			$this->vote->setUserId($this->acl->getUserId());
			$this->vote->setVoteOptionText($option->getPollOptionText());
			$this->vote->setVoteOptionId($option->getId());
			$this->vote->setVoteAnswer($setTo);
			$this->voteMapper->insert($this->vote);
		}
		$this->logService->setLog($this->vote->getPollId(), Log::MSG_ID_SETVOTE, $this->vote->getUserId());
		$this->watchService->writeUpdate($this->vote->getPollId(), Watch::OBJECT_VOTES);
		return $this->vote;
	}

	/**
	 * Remove user from poll
	 */
	public function delete(int $pollId, string $userId): string {
		$this->acl->setPollId($pollId)->request(Acl::PERMISSION_POLL_EDIT);
		$this->voteMapper->deleteByPollAndUser($pollId, $userId);
		$this->watchService->writeUpdate($pollId, Watch::OBJECT_VOTES);
		return $userId;
	}
}
