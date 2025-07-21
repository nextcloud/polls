<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Event\VoteDeletedOrphanedEvent;
use OCA\Polls\Event\VoteSetEvent;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Exceptions\VoteLimitExceededException;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;

class VoteService {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private OptionMapper $optionMapper,
		private PollMapper $pollMapper,
		private Vote $vote,
		private VoteMapper $voteMapper,
		private UserSession $userSession,
	) {
	}

	/**
	 * Read all votes of a poll based on the poll id and return list as array
	 *
	 * @return Vote[]
	 */
	public function list(int $pollId): array {
		$poll = $this->pollMapper->get($pollId, withRoles: true);
		$poll->request(Poll::PERMISSION_POLL_VIEW);

		if (!$poll->getIsAllowed(Poll::PERMISSION_POLL_RESULTS_VIEW)) {
			// Just return the participants votes, no further anoymizing or obfuscating is nessecary
			return $this->voteMapper->findByPollAndUser($pollId, ($this->userSession->getCurrentUserId()));
		}

		$votes = $this->voteMapper->findByPoll($pollId);

		return $votes;
	}

	private function checkLimits(Option $option): void {
		// check, if the optionlimit is reached or exceeded, if one is set
		if ($option->getIsLockedByOptionLimit()) {
			throw new VoteLimitExceededException;
		}

		if ($option->getIsLockedByVotesLimit()) {
			throw new VoteLimitExceededException;
		}
		return;
	}

	private function checkVoteLimit(Option $option): void {
		// check, if the optionlimit is reached or exceeded, if one is set
		if ($option->getIsLockedByOptionLimit()) {
			throw new VoteLimitExceededException();
		}

		if ($option->getIsLockedByVotesLimit()) {
			throw new VoteLimitExceededException;
		}
		return;
	}

	/**
	 * Set vote
	 */
	public function set(Option|int $optionOrOptionIdoptionId, string $setTo): ?Vote {
		if ($optionOrOptionIdoptionId instanceof Option) {
			$option = $optionOrOptionIdoptionId;
		} else {
			$option = $this->optionMapper->find($optionOrOptionIdoptionId);
		}
		$poll = $this->pollMapper->get($option->getPollId(), withRoles: true);
		$poll->request(Poll::PERMISSION_VOTE_EDIT);

		if ($option->getIsLocked()) {
			$this->checkVoteLimit($option);
			throw new NotFoundException();
		}

		try {
			$this->vote = $this->voteMapper->findSingleVote($poll->getId(), $option->getPollOptionText(), $this->userSession->getCurrentUserId());

			if ($setTo === $this->vote->getVoteAnswer()) {
				return $this->vote;
			}

			if ($setTo === Vote::VOTE_YES) {
				$this->checkLimits($option);
			}

			//  delete no votes, if poll setting is set to useNo === 0
			$deleteVoteInsteadOfNoVote = in_array(trim($setTo), [Vote::VOTE_NO, '']) && !boolval($poll->getUseNo());

			if ($deleteVoteInsteadOfNoVote) {
				$this->vote->setVoteAnswer('');
				$this->voteMapper->delete($this->vote);
			} else {
				$this->vote->setVoteAnswer($setTo);
				$this->vote = $this->voteMapper->update($this->vote);
			}
		} catch (DoesNotExistException $e) {
			// Vote does not exist, insert as new Vote
			$this->vote = new Vote();

			$this->vote->setPollId($poll->getId());
			$this->vote->setUserId($this->userSession->getCurrentUserId());
			$this->vote->setVoteOptionText($option->getPollOptionText());
			$this->vote->setVoteOptionId($option->getId());
			$this->vote->setVoteAnswer($setTo);
			$this->vote = $this->voteMapper->insert($this->vote);
		}

		$this->eventDispatcher->dispatchTyped(new VoteSetEvent($this->vote));
		return $this->vote;
	}

	/**
	 * Get all votes of a poll, which are not assigned to an option
	 *
	 * @param int $pollId poll id of the poll the votes get deleted from
	 * @return Vote[]
	 */
	public function getOprhanedVotes(int $pollId): array {
		$poll = $this->pollMapper->get($pollId, withRoles: true);
		$poll->request(Poll::PERMISSION_POLL_EDIT);

		// get all votes of the poll, which are not assigned to an option
		return $this->voteMapper->findOrphanedByPoll($pollId);
	}

	/**
	 * Delete all votes of a poll, which are not assigned to an option
	 *
	 * @param int $pollId poll id of the poll the votes get deleted from
	 * @return Vote[]
	 */
	public function deleteOrphanedVotes(int $pollId): array {
		$poll = $this->pollMapper->get($pollId, withRoles: true);
		$poll->request(Poll::PERMISSION_VOTE_FOREIGN_CHANGE);

		// delete all votes of the poll, which are not assigned to an option
		$votes = $this->voteMapper->findOrphanedByPoll($pollId);
		foreach ($votes as $vote) {
			$this->voteMapper->delete($vote);
			// TODO: rework notification methods
			// keep this dispatch as reminder
			// $this->eventDispatcher->dispatchTyped(new VoteDeletedOrphanedEvent($this->vote, false));
		}
		return $votes;
	}

	/**
	 * Remove user from poll
	 *
	 * @param int $pollId poll id of the poll the votes get deleted from
	 * @param string $userId user id of the user, the votes get deleted from
	 * @param bool $deleteOnlyOrphaned - false deletes all votes of the specified user, true only the orphaned votes aka votes without an option
	 */
	public function deleteUserFromPoll(int $pollId, string $userId = '', bool $deleteOnlyOrphaned = false): string {
		if ($userId === '') {
			// if no user set, use current user
			$userId = $this->userSession->getCurrentUserId();
		}

		// Set default right to delete all votes of the user
		$checkRight = Poll::PERMISSION_VOTE_FOREIGN_CHANGE;
		if ($userId === $this->userSession->getCurrentUserId()) {
			// allow current user to remove his votes
			$checkRight = Poll::PERMISSION_VOTE_EDIT;
		}

		$this->pollMapper->get($pollId, withRoles: true)->request($checkRight);

		return $this->delete($pollId, $userId, $deleteOnlyOrphaned);
	}

	/**
	 * Remove user from poll
	 * @param int $pollId poll id of the poll the votes get deleted from
	 * @param string $userId user id of the user, the votes get deleted from. No user affects the current user
	 * @param bool $deleteOnlyOrphaned - false deletes all votes of the specified user, true only the orphaned votes aka votes without an option
	 */
	private function delete(int $pollId, string $userId, bool $deleteOnlyOrphaned = false): string {
		if ($deleteOnlyOrphaned) {
			$votes = $this->voteMapper->findOrphanedByPollandUser($pollId, $userId);
			foreach ($votes as $vote) {
				$this->voteMapper->delete($vote);
			}
			return $userId;
		}
		// fake a vote so that the event can be triggered
		// suppress logging of this action
		$this->vote = new Vote();
		$this->vote->setPollId($pollId);
		$this->vote->setUserId($userId);
		$this->voteMapper->deleteByPollAndUserId($pollId, $userId);

		$this->eventDispatcher->dispatchTyped(new VoteSetEvent($this->vote, false));
		return $userId;
	}
}
