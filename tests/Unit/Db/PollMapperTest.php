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

namespace OCA\Polls\Tests\Unit\Db;

use League\FactoryMuffin\Faker\Facade as Faker;
use OCP\IDBConnection;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;

class PollMapperTest extends UnitTestCase {

	/** @var IDBConnection */
	private $con;

	/** @var PollMapper */
	private $pollMapper;

	/** @var array */
	private $polls = [];


	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->con = \OC::$server->getDatabaseConnection();
		$this->pollMapper = new PollMapper($this->con);

		$this->polls = [
			$this->fm->instance('OCA\Polls\Db\Poll'),
			$this->fm->instance('OCA\Polls\Db\Poll'),
			$this->fm->instance('OCA\Polls\Db\Poll')
		];
		foreach ($this->polls as &$poll) {
			$poll = $this->pollMapper->insert($poll);
		}
		unset($poll);
	}

	/**
	 * testFindAll
	 */
	public function testFindAll() {
		$this->assertEquals(count($this->optionMapper->findAll()), count($this->polls));
	}

	/**
	 * testUpdate
	 * includes testFind
	 */
	public function testUpdate() {
		foreach ($this->polls as &$poll) {
			$before = $this->optionMapper->find($poll->getId());
			$this->assertEquals($poll, $before);

			$newTitle = Faker::sentence(10);
			$newDescription = Faker::paragraph();
			$poll->setTitle($newTitle());
			$poll->setDescription($newDescription());

			$this->assertEquals($poll, $this->pollMapper->update($poll));
			$this->assertNotEquals($option, $before);
		}
		unset($poll);
	}

	/**
	 * Delete the previously created entry from the database.
	 */
	public function testDelete() {
		foreach ($this->polls as $poll) {
			$this->assertInstanceOf(Poll::class, $this->pollMapper->delete($poll));
		}
	}

	/**
	 * tearDown
	 */
	public function tearDown(): void {
		parent::tearDown();
		// no tidy neccesary, polls got deleted via testDelete()
	}
}
