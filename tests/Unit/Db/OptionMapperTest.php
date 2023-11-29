<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Kai Schröer <git@schroeer.co>
 *
 * @author Kai Schröer <git@schroeer.co>
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

namespace OCA\Polls\Db;

use OCP\IDBConnection;
use OCA\Polls\Tests\Unit\UnitTestCase;

use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;
use Psr\Log\LoggerInterface;

class OptionMapperTest extends UnitTestCase {
	private IDBConnection $con;
	private ISession $session;
	private IUserManager $userManager;
	private IUserSession $userSession;
	private LoggerInterface $logger;
	private OptionMapper $optionMapper;
	private VoteMapper $voteMapper;
	private PollMapper $pollMapper;
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
		$this->logger = Server::get(LoggerInterface::class);
		$this->session = Server::get(ISession::class);
		$this->userManager = Server::get(IUserManager::class);
		$this->userSession = Server::get(IUserSession::class);
		$this->session->set('ncPollsUserId', 'TestUser');


		$this->voteMapper = new VoteMapper($this->con);
		$this->userMapper = new UserMapper($this->con, $this->session, $this->userSession, $this->userManager, $this->logger);
		$this->optionMapper = new OptionMapper($this->con, $this->session, $this->userMapper);
		$this->pollMapper = new PollMapper($this->con);

		$this->polls = [
			$this->fm->instance('OCA\Polls\Db\Poll')
		];

		foreach ($this->polls as &$poll) {
			$poll = $this->pollMapper->insert($poll);

			for ($count = 0; $count < 2; $count++) {
				$option = $this->fm->instance('OCA\Polls\Db\Option');
				$option->setPollId($poll->getId());
				array_push($this->options, $this->optionMapper->add($option));
				$vote = $this->fm->instance('OCA\Polls\Db\Vote');
				$vote->setPollId($option->getPollId());
				$vote->setUserId('TestUser');
				$vote->setVoteOptionText($option->getPollOptionText());
				array_push($this->votes, $this->voteMapper->insert($vote));
			}
		}
		unset($poll);
	}

	/**
	 * testFind
	 */
	public function testFind() {
		foreach ($this->options as $option) {
			$this->assertInstanceOf(Option::class, $this->optionMapper->find($option->getId()));
		}
	}

	/**
	 * testFindByPoll
	 */
	public function testFindByPoll() {
		foreach ($this->polls as $poll) {
			$this->assertTrue(count($this->optionMapper->findByPoll($poll->getId())) > 0);
		}
	}

	/**
	 * testUpdate
	 * includes testFind
	 */
	public function testUpdate() {
		$i = 0;
		foreach ($this->options as &$option) {
			$option->setPollOptionText('Changed option' . ++$i);
			$this->assertInstanceOf(Option::class, $this->optionMapper->update($option));
		}
	}

	/**
	 * testDelete
	 */
	public function testDelete() {
		foreach ($this->options as $option) {
			$this->assertInstanceOf(Option::class, $this->optionMapper->delete($option));
		}
	}

	/**
	 * tearDown
	 */
	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->polls as $poll) {
			$this->pollMapper->delete($poll);
		}
	}
}
