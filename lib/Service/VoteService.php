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
use OCA\Polls\Db\Vote;
use OCA\Polls\Model\Acl;

class VoteService {

	/** @var VoteMapper */
	private $voteMapper;

	/** @var Vote */
	private $vote;

	/** @var OptionMapper */
	private $optionMapper;

	/** @var AnonymizeService */
	private $anonymizer;

	/** @var LogService */
	private $logService;

	/** @var Acl */
	private $acl;

	public function __construct(
		VoteMapper $voteMapper,
		Vote $vote,
		OptionMapper $optionMapper,
		AnonymizeService $anonymizer,
		LogService $logService,
		Acl $acl
	) {
		$this->voteMapper = $voteMapper;
		$this->vote = $vote;
		$this->optionMapper = $optionMapper;
		$this->anonymizer = $anonymizer;
		$this->logService = $logService;
		$this->acl = $acl;
	}

	/**
	 * Read all votes of a poll based on the poll id and return list as array
	 */
	public function list(int $pollId = 0, string $token = ''): array {
		if ($token) {
			$this->acl->setToken($token);
		} else {
			$this->acl->setPollId($pollId)->requestView();
		}

		try {
			if (!$this->acl->getAllowSeeResults()) {
				return $this->voteMapper->findByPollAndUser($this->acl->getpollId(), $this->acl->getUserId());
			} elseif (!$this->acl->getAllowSeeUsernames()) {
				$this->anonymizer->set($this->acl->getpollId(), $this->acl->getUserId());
				return $this->anonymizer->getVotes();
			} else {
				return $this->voteMapper->findByPoll($this->acl->getpollId());
			}
		} catch (DoesNotExistException $e) {
			return [];
		}
	}

	/**
	 * Set vote
	 */
	public function set(int $optionId, string $setTo, string $token = ''): Vote {
		$option = $this->optionMapper->find($optionId);

		if ($token) {
			$this->acl->setToken($token)->requestVote();
			if (intval($option->getPollId()) !== $this->acl->getPollId()) {
				throw new NotAuthorizedException;
			}
		} else {
			$this->acl->setPollId($option->getPollId())->requestVote();
		}

		if ($setTo === 'yes') {
			$this->acl->requestYesVotes();
		}

		if ($setTo === 'yes'
			&& $this->acl->getOptionLimit()
			&& $this->voteMapper->countYesVotesByOption($this->acl->getPollId(), $option->getPollOptionText())) {
			throw new VoteLimitExceededException;
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
		$this->logService->setLog($this->acl->getPollId(), Log::MSG_ID_SETVOTE, $this->vote->getUserId());
		return $this->vote;
	}

	/**
	 * Remove user from poll
	 */
	public function delete(int $pollId, string $userId): string {
		$this->acl->setPollId($pollId)->requestEdit();
		$this->voteMapper->deleteByPollAndUser($pollId, $userId);
		return $userId;
	}
}
