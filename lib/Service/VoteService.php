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
use OCP\EventDispatcher\IEventDispatcher;

use OCA\Polls\Exceptions\VoteLimitExceededException;

use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\Watch;
use OCA\Polls\Event\VoteEvent;
use OCA\Polls\Model\Acl;

class VoteService {

	/** @var Acl */
	private $acl;

	/** @var AnonymizeService */
	private $anonymizer;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var OptionMapper */
	private $optionMapper;

	/** @var Vote */
	private $vote;

	/** @var VoteMapper */
	private $voteMapper;

	public function __construct(
		Acl $acl,
		AnonymizeService $anonymizer,
		IEventDispatcher $eventDispatcher,
		OptionMapper $optionMapper,
		Vote $vote,
		VoteMapper $voteMapper
	) {
		$this->acl = $acl;
		$this->anonymizer = $anonymizer;
		$this->eventDispatcher = $eventDispatcher;
		$this->optionMapper = $optionMapper;
		$this->vote = $vote;
		$this->voteMapper = $voteMapper;
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
			if (!$this->acl->getIsAllowed(Acl::PERMISSION_POLL_RESULTS_VIEW)) {
				return $this->voteMapper->findByPollAndUser($this->acl->getpollId(), $this->acl->getUserId());
			}

			if (!$this->acl->getIsAllowed(Acl::PERMISSION_POLL_USERNAMES_VIEW)) {
				$this->anonymizer->set($this->acl->getpollId(), $this->acl->getUserId());
				return $this->anonymizer->getVotes();
			}

			return $this->voteMapper->findByPoll($this->acl->getpollId());
		} catch (DoesNotExistException $e) {
			return [];
		}
	}

	private function checkLimits(Option $option, string $userId):void {

		// check, if the optionlimit is reached or exceeded, if one is set
		if ($this->acl->getPoll()->getOptionLimit() > 0) {
			if ($this->acl->getPoll()->getOptionLimit() <= count($this->voteMapper->getYesVotesByOption($option->getPollId(), $option->getPollOptionText()))) {
				throw new VoteLimitExceededException;
			}
		}

		// exit, if no vote limit is set
		if ($this->acl->getPoll()->getVoteLimit() < 1) {
			return;
		}

		// Only count votes, which match to an actual existing option.
		// Explanation: If an option is deleted, the corresponding votes are not deleted.
		$pollOptionTexts = array_map(function ($option) {
			return $option->getPollOptionText();
		}, $this->optionMapper->findByPoll($option->getPollId()));

		$votecount = 0;
		$votes = $this->voteMapper->getYesVotesByParticipant($option->getPollId(), $userId);
		foreach ($votes as $vote) {
			if (in_array($vote->getVoteOptionText(), $pollOptionTexts)) {
				$votecount++;
			}
		}

		if ($this->acl->getPoll()->getVoteLimit() <= $votecount) {
			throw new VoteLimitExceededException;
		}
	}

	/**
	 * Set vote
	 */
	public function set(int $optionId, string $setTo, string $token = ''): ?Vote {
		$option = $this->optionMapper->find($optionId);

		if ($token) {
			$this->acl->setToken($token, Acl::PERMISSION_VOTE_EDIT, $option->getPollId());
		} else {
			$this->acl->setPollId($option->getPollId(), Acl::PERMISSION_VOTE_EDIT);
		}

		if ($setTo === 'yes') {
			$this->checkLimits($option, $this->acl->getUserId());
		}

		try {
			$this->vote = $this->voteMapper->findSingleVote($this->acl->getPollId(), $option->getPollOptionText(), $this->acl->getUserId());

			if (in_array(trim($setTo), ['no', '']) && !$this->acl->getPoll()->getUseNo()) {
				$this->vote->setVoteAnswer('');
				$this->voteMapper->delete($this->vote);
			} else {
				$this->vote->setVoteAnswer($setTo);
				$this->voteMapper->update($this->vote);
			}

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

		$this->eventDispatcher->dispatchTyped(new VoteEvent($this->vote));
		return $this->vote;
	}

	/**
	 * Remove user from poll
	 */
	public function delete(int $pollId, string $userId): string {
		$this->acl->setPollId($pollId, Acl::PERMISSION_POLL_EDIT);
		$this->voteMapper->deleteByPollAndUserId($pollId, $userId);
		return $userId;
	}
}
