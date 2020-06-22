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
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Service\AnonymizeService;
use OCA\Polls\Service\LogService;
use OCA\Polls\Model\Acl;

class VoteService  {

	private $voteMapper;
	private $vote;
	private $optionMapper;
	private $anonymizer;
	private $logService;
	private $acl;

	/**
	 * VoteController constructor.
	 * @param VoteMapper $voteMapper
	 * @param Vote $vote
	 * @param OptionMapper $optionMapper
	 * @param AnonymizeService $anonymizer
	 * @param LogService $logService
	 * @param Acl $acl
	 */
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
	 * Get all votes of given poll
	 * Read all votes of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @param string $token
	 * @return Vote
	 */
	public function list($pollId = 0, $token = '') {
		if (!$this->acl->setPollIdOrToken($pollId, $token)->getAllowView()) {
			throw new NotAuthorizedException;
		}

		if (!$this->acl->getAllowSeeResults()) {
			return $this->voteMapper->findByPollAndUser($this->acl->getpollId(), $this->acl->getUserId());
		} elseif (!$this->acl->getAllowSeeUsernames()) {
			$this->anonymizer->set($this->acl->getpollId(), $this->acl->getUserId());
			return $this->anonymizer->getVotes();
		} else {
			return $this->voteMapper->findByPoll($this->acl->getpollId());
		}
	}

	/**
	 * set
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @param Array $option
	 * @param string $setTo
	 * @param string $token
	 * @return Vote
	 */
	public function set($pollId = 0, $pollOptionText, $setTo, $token = '') {

		if (!$this->acl->setPollIdOrToken($pollId, $token)->getAllowVote()) {
			throw new NotAuthorizedException;
		}

		$option = $this->optionMapper->findByPollAndText($this->acl->getpollId(), $pollOptionText);

		try {
			$this->vote = $this->voteMapper->findSingleVote($this->acl->getpollId(), $option->getPollOptionText(), $this->acl->getUserId());
			$this->vote->setVoteAnswer($setTo);
			$this->voteMapper->update($this->vote);

		} catch (DoesNotExistException $e) {
			// Vote does not exist, insert as new Vote
			$this->vote = new Vote();

			$this->vote->setPollId($this->acl->getpollId());
			$this->vote->setUserId($this->acl->getUserId());
			$this->vote->setVoteOptionText($option->getPollOptionText());
			$this->vote->setVoteOptionId($option->getId());
			$this->vote->setVoteAnswer($setTo);
			$this->voteMapper->insert($this->vote);

		} finally {
			$this->logService->setLog($this->vote->getPollId(), 'setVote', $this->vote->getUserId());
			return $this->vote;
		}
	}

	/**
	 * delete
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $voteId
	 * @param string $userId
	 * @param integer $pollId
	 * @return Vote
	 */
	public function delete($pollId, $userId) {

		if (!$this->acl->setPollId($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$votes = $this->voteMapper->deleteByPollAndUser($pollId, $userId);
	}

}
