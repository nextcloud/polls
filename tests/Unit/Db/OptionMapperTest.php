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
use League\FactoryMuffin\Faker\Facade as Faker;

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

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();

		$this->optionMapper = new OptionMapper($this->con);
		$this->pollMapper = new PollMapper($this->con);

		$yesterdayTs = function () {
			$date = new DateTime('yesterday');
			return $date->getTimestamp();
		};

		$todayTs = function () {
			$date = new DateTime('today');
			return $date->getTimestamp();
		};

		$todayTs = function () {
			$date = new DateTime('tomorrow');
			return $date->getTimestamp();
		};

		$this->polls = [
			$this->createPollEntity(Poll::TYPE_TEXT, Faker::text(255), 'admin')
		];

		foreach ($this->polls as $poll) {
			$entry = $this->pollMapper->insert($poll);
			$entry->resetUpdatedFields();
			$this->pollById[$entry->getId()] = $entry;
		}

		$this->options = [
			$this->createOptionEntity(1, Faker::text(255), 1),
			$this->createOptionEntity(1, Faker::text(255), 2),
			$this->createOptionEntity(1, Faker::text(255), 3)

		];
		foreach ($this->options as $option) {
			$entry = $this->optionMapper->insert($option);
			$entry->resetUpdatedFields();
			$this->optionById[$entry->getId()] = $entry;
		}

	}

	private function createPollEntity($type, $title, $owner) {
		$poll = new Poll();
		$poll->setType($type);
		$poll->setCreated(time());
		$poll->setOwner($owner);
		$poll->setTitle($title);
		$poll->setDescription(Faker::text(255));
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
		$option->setType($pollOptionText);
		$option->setTimestamp(time());
		$option->setOrder($order);
		$option->setconfirmed(0);
		return $option;
	}

	/**
	 * Find the previously created entries from the database.
	 */
	public function testFind(array $options) {
		foreach ($this->options as $id => $option) {
			$this->assertEquals($option, $this->optionMapper->find($id));
		}
	}

	/**
	 * Find the previously created entries from the database.
	 */
	public function testFindByPoll(array $options) {
		foreach ($polls as $id => $poll) {
			$this->assertTrue(count($this->optionMapper->findByPoll($id)) > 0);
		}
	}

	/**
	 * Update the previously created entry and persist the changes.
	 */
	public function testUpdate(array $options) {
		foreach ($options as $option) {
			$newPollOptionText = Faker::text(255);
			$option->setPollOptionText(Faker::text(255));
			$this->assertEquals($option, $this->optionMapper->update($option));
		}
	}

	/**
	 * Delete the previously created entries from the database.
	 *
	 * @depends testUpdate
	 */
	public function testDelete(array $options) {
		foreach ($options as $option) {
			$this->assertInstanceOf(Option::class, $this->optionMapper->delete($option));
		}
	}

	public function tearDown(): void {
		parent::tearDown();
		foreach ($this->polls as $poll) {
			$this->pollMapper->delete($poll);
		}
	}
}
