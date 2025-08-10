<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use League\FactoryMuffin\Faker\Facade as Faker;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\Server;

class PollMapperTest extends UnitTestCase {
	private PollMapper $pollMapper;
	/** @var Poll[] $polls*/
	private array $polls = [];

	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->pollMapper = Server::get(PollMapper::class);

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
	 * testUpdate
	 */
	public function testUpdate() {
		foreach ($this->polls as &$poll) {
			$newTitle = Faker::sentence(10);
			$newDescription = Faker::paragraph();
			$poll->setTitle($newTitle());
			$poll->setDescription($newDescription());

			$this->assertInstanceOf(Poll::class, $this->pollMapper->update($poll));
		}
		unset($poll);
	}

	/**
	 * Delete the previously created entry from the database.
	 */
	public function testFind() {
		foreach ($this->polls as $poll) {
			$this->assertInstanceOf(Poll::class, $this->pollMapper->get($poll->getId()));
		}
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
