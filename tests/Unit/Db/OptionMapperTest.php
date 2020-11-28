<?php declare(strict_types=1);
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

namespace OCA\Polls\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IDBConnection;
use Test\AppFramework\Db\MapperTestUtility;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;

/**
 * @group DB
 */
class OptionMapperTest extends MapperTestUtility {

	/** @var IDBConnection */
	private $con;

	/** @var OptionMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $optionMapper;

	/** @var PollMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $pollMapper;

	/** @var array */
	private $polls;

	/** @var array */
	private $options;

	/** @var array */
	private $pollsById;

	/** @var array */
	private $optionsById;

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();

		$this->optionMapper = new OptionMapper($this->con);
		$this->pollMapper = new PollMapper($this->con);

		$this->polls = [
			$this->createPollEntity(Poll::TYPE_TEXT, 'Poll Title', 'admin')
		];

		foreach ($this->polls as $poll) {
			$entry = $this->pollMapper->insert($poll);
			$entry->resetUpdatedFields();
			$this->pollsById[$entry->getId()] = $entry;
		}

		foreach ($this->pollsById as $id => $polls) {
			$this->options = [
				$this->createOptionEntity($id, 'Option 1', 1),
				$this->createOptionEntity($id, 'Option 2', 2),
				$this->createOptionEntity($id, 'Option 3', 3)
			];
		}

		foreach ($this->options as $option) {
			$entry = $this->optionMapper->insert($option);
			$entry->resetUpdatedFields();
			$this->optionsById[$entry->getId()] = $entry;
		}

	}

	private function createPollEntity($type, $title, $owner) {
		$poll = new Poll();
		$poll->setType($type);
		$poll->setCreated(time());
		$poll->setOwner($owner);
		$poll->setTitle($title);
		$poll->setDescription('Description');
		$poll->setAccess(Poll::ACCESS_PUBLIC);
		$poll->setExpire(0);
		$poll->setAnonymous(0);
		$poll->setFullAnonymous(0);
		$poll->setAllowMaybe(0);
		$poll->setVoteLimit(0);
		$poll->setSettings('{"someJSON":0}');
		$poll->setOptions('["yes","no","maybe"]');
		$poll->setShowResults(Poll::SHOW_RESULTS_ALWAYS);
		$poll->setDeleted(0);
		$poll->setAdminAccess(0);
		$poll->setImportant(0);
		return $poll;
	}

	private function createOptionEntity($pollId, $pollOptionText, $order) {
		$option = new Option();
		$option->setPollId($pollId);
		$option->setPollOptionText($pollOptionText);
		$option->setTimestamp(0);
		$option->setOrder($order);
		$option->setconfirmed(0);
		return $option;
	}

	/**
	 * Find the previously created entries from the database.
	 */
	public function testFind() {
		foreach ($this->optionsById as $id => $option) {
			$this->assertEquals($option, $this->optionMapper->find($id));
		}
	}

	/**
	 * Find the previously created entries from the database.
	 */
	public function testFindByPoll() {
		foreach ($this->pollsById as $id => $poll) {
			$this->assertTrue(count($this->optionMapper->findByPoll($id)) > 0);
		}
	}

	/**
	 * Update the previously created entry and persist the changes.
	 */
	public function testUpdate() {
		foreach ($this->optionsById as $id => $option) {
			$found = $this->optionMapper->find($id);
			$found->setPollOptionText('Changed option');
			$this->assertEquals($found, $this->optionMapper->update($found));
		}
	}

	/**
	 * Delete the previously created entries from the database.
	 */
	public function testDelete() {
		foreach ($this->optionsById as $id => $option) {
			$found = $this->optionMapper->find($id);
			$this->assertInstanceOf(Option::class, $this->optionMapper->delete($found));
		}
	}

	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->polls as $poll) {
			$this->pollMapper->delete($poll);
		}
	}
}
