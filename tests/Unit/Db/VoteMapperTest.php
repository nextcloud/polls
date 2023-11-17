<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Kai Schröer <git@schroeer.co>
 *
 * @author Kai Schröer <git@schroeer.co>
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

namespace OCA\Polls\Tests\Unit\Db;

use OCP\IDBConnection;
use OCA\Polls\Tests\Unit\UnitTestCase;

use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\UserMapper;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Server;

class VoteMapperTest extends UnitTestCase {
	private IDBConnection $con;
	private ISession $session;
	private IUserSession $userSession;
	private VoteMapper $voteMapper;
	private PollMapper $pollMapper;
	private OptionMapper $optionMapper;
	private UserMapper $userMapper;
	private array $polls = [];
	private array $options = [];
	private array $votes = [];

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = Server::get(IDBConnection::class);
		$this->session = Server::get(ISession::class);
		$this->userSession = Server::get(IUserSession::class);
		$this->session->set('ncPollsUserId', 'TestUser');

		$this->pollMapper = new PollMapper($this->con);
		$this->voteMapper = new VoteMapper($this->con);
		$this->userMapper = new UserMapper($this->con, $this->session, $this->userSession);
		$this->optionMapper = new OptionMapper($this->con, $this->session, $this->userMapper);

		$this->polls = [
			$this->fm->instance('OCA\Polls\Db\Poll')
		];

		foreach ($this->polls as &$poll) {
			$poll = $this->pollMapper->insert($poll);

			for ($optionsCount = 0; $optionsCount < 2; $optionsCount++) {
				$option = $this->fm->instance('OCA\Polls\Db\Option');
				$option->setPollId($poll->getId());
				array_push($this->options, $this->optionMapper->add($option));
				$vote = $this->fm->instance('OCA\Polls\Db\Vote');
				$vote->setPollId($option->getPollId());
				$vote->setUserId('voter');
				$vote->setVoteOptionText($option->getPollOptionText());
				array_push($this->votes, $this->voteMapper->insert($vote));
			}
		}
		unset($poll);
	}


	/**
	 * testFindByPoll
	 */
	public function testFindByPoll() {
		foreach ($this->polls as $poll) {
			$this->assertTrue(count($this->voteMapper->findByPoll($poll->getId())) > 0);
		}
	}

	/**
	 * testFindByPollAndUser
	 */
	public function testFindByPollAndUser() {
		foreach ($this->polls as $poll) {
			$this->assertTrue(count($this->voteMapper->findByPollAndUser($poll->getId(), 'voter')) > 0);
		}
	}

	/**
	 * testFindSingleVote
	 */
	public function testFindSingleVote() {
		foreach ($this->votes as $vote) {
			$this->assertInstanceOf(Vote::class, $this->voteMapper->findSingleVote($vote->getPollId(), $vote->getVoteOptionText(), $vote->getUserId()));
		}
	}

	/**
	 * testParticipantsByPoll
	 */
	public function testParticipantsByPoll() {
		foreach ($this->polls as $poll) {
			$this->assertTrue(count($this->voteMapper->findParticipantsByPoll($poll->getId())) > 0);
		}
	}

	/**
	 * testParticipantsByPoll
	 */
	public function testFindParticipantsVotes() {
		foreach ($this->votes as $vote) {
			$this->assertTrue(count($this->voteMapper->findParticipantsVotes($vote->getPollId(), $vote->getUserId())) > 0);
		}
	}

	/**
	 * testUpdate
	 */
	public function testUpdate() {
		foreach ($this->votes as &$vote) {
			$vote->setVoteAnswer('no');
			$this->assertInstanceOf(Vote::class, $this->voteMapper->update($vote));
		}
		unset($vote);
	}

	/**
	 * testDeleteByPollAndUser
	 */
	public function testDeleteByPollAndUserId() {
		foreach ($this->polls as $poll) {
			$this->assertNull($this->voteMapper->deleteByPollAndUserId($poll->getId(), 'voter'));
		}
	}

	/**
	 * tearDown
	 */
	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->options as $option) {
			$this->optionMapper->delete($option);
		}
		foreach ($this->polls as $poll) {
			$this->pollMapper->delete($poll);
		}
	}
}
