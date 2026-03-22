<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Exceptions\AlreadyDeletedException;
use OCA\Polls\Exceptions\EmptyTitleException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Exceptions\InvalidShowResultsException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Service\PollService;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCA\Polls\UserSession;
use OCP\Server;

class PollServiceTest extends UnitTestCase {
	private PollService $pollService;
	private PollMapper $pollMapper;
	private Poll $poll;

	protected function setUp(): void {
		parent::setUp();
		\OC_User::setUserId('admin');
		Server::get(UserSession::class)->cleanSession();

		$this->pollService = Server::get(PollService::class);
		$this->pollMapper = Server::get(PollMapper::class);

		// Create a base poll for read/update/archive/close tests
		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll->setOwner('admin');
		$this->poll = $this->pollMapper->insert($poll);
	}

	protected function tearDown(): void {
		parent::tearDown();
		try {
			$this->pollMapper->delete($this->poll);
		} catch (\Exception $e) {
			// already deleted by test
		}
	}

	// --- add ---

	public function testAddCreatesTextPoll(): void {
		$poll = $this->pollService->add(Poll::TYPE_TEXT, 'My Text Poll');
		$this->assertInstanceOf(Poll::class, $poll);
		$this->assertSame(Poll::TYPE_TEXT, $poll->getType());
		$this->assertSame('My Text Poll', $poll->getTitle());
		$this->assertSame('admin', $poll->getOwner());
		$this->pollMapper->delete($poll);
	}

	public function testAddCreatesDatePoll(): void {
		$poll = $this->pollService->add(Poll::TYPE_DATE, 'My Date Poll');
		$this->assertInstanceOf(Poll::class, $poll);
		$this->assertSame(Poll::TYPE_DATE, $poll->getType());
		$this->pollMapper->delete($poll);
	}

	public function testAddThrowsOnInvalidType(): void {
		$this->expectException(InvalidPollTypeException::class);
		$this->pollService->add('invalidType', 'Title');
	}

	public function testAddThrowsOnEmptyTitle(): void {
		$this->expectException(EmptyTitleException::class);
		$this->pollService->add(Poll::TYPE_TEXT, '');
	}

	// --- get ---

	public function testGetReturnsExistingPoll(): void {
		$result = $this->pollService->get($this->poll->getId());
		$this->assertInstanceOf(Poll::class, $result);
		$this->assertSame($this->poll->getId(), $result->getId());
	}

	public function testGetThrowsOnNonExistentPoll(): void {
		$this->expectException(NotFoundException::class);
		$this->pollService->get(PHP_INT_MAX);
	}

	// --- update ---

	public function testUpdateChangesTitle(): void {
		$result = $this->pollService->update($this->poll->getId(), ['title' => 'Updated Title']);
		$this->assertSame('Updated Title', $result['poll']->getTitle());
	}

	public function testUpdateReturnsDiff(): void {
		$result = $this->pollService->update($this->poll->getId(), ['title' => 'Another Title']);
		$this->assertArrayHasKey('poll', $result);
		$this->assertArrayHasKey('diff', $result);
		$this->assertArrayHasKey('changes', $result);
	}

	public function testUpdateThrowsOnEmptyTitle(): void {
		$this->expectException(EmptyTitleException::class);
		$this->pollService->update($this->poll->getId(), ['title' => '']);
	}

	public function testUpdateThrowsOnInvalidShowResults(): void {
		$this->expectException(InvalidShowResultsException::class);
		$this->pollService->update($this->poll->getId(), ['showResults' => 'invalidValue']);
	}

	public function testUpdateChangesShowResults(): void {
		$result = $this->pollService->update($this->poll->getId(), ['showResults' => Poll::SHOW_RESULTS_CLOSED]);
		$this->assertSame(Poll::SHOW_RESULTS_CLOSED, $result['poll']->getShowResults());
	}

	// --- toggleArchive ---

	public function testToggleArchiveArchivesPoll(): void {
		$result = $this->pollService->toggleArchive($this->poll->getId());
		$this->assertGreaterThan(0, $result->getDeleted());
	}

	public function testToggleArchiveRestoresPoll(): void {
		// Archive first
		$this->pollService->toggleArchive($this->poll->getId());
		// Then restore
		$result = $this->pollService->toggleArchive($this->poll->getId());
		$this->assertSame(0, $result->getDeleted());
	}

	// --- close / reopen ---

	public function testCloseSetsExpiryInPast(): void {
		$result = $this->pollService->close($this->poll->getId());
		$this->assertLessThan(time(), $result->getExpire());
		$this->assertGreaterThan(0, $result->getExpire());
	}

	public function testReopenClearsExpiry(): void {
		$this->pollService->close($this->poll->getId());
		$result = $this->pollService->reopen($this->poll->getId());
		$this->assertSame(0, $result->getExpire());
	}

	// --- clone ---

	public function testCloneCreatesNewPoll(): void {
		$clone = $this->pollService->clone($this->poll->getId());
		$this->assertInstanceOf(Poll::class, $clone);
		$this->assertNotSame($this->poll->getId(), $clone->getId());
		$this->assertSame('Clone of ' . $this->poll->getTitle(), $clone->getTitle());
		$this->assertSame('admin', $clone->getOwner());
		$this->pollMapper->delete($clone);
	}

	// --- delete ---

	public function testDeleteRemovesPoll(): void {
		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll->setOwner('admin');
		$poll = $this->pollMapper->insert($poll);

		$result = $this->pollService->delete($poll->getId());
		$this->assertInstanceOf(Poll::class, $result);

		$this->expectException(NotFoundException::class);
		$this->pollService->get($poll->getId());
	}

	public function testDeleteThrowsOnNonExistentPoll(): void {
		$this->expectException(AlreadyDeletedException::class);
		$this->pollService->delete(PHP_INT_MAX);
	}

	// --- getValidEnum ---

	public function testGetValidEnumReturnsExpectedKeys(): void {
		$enum = $this->pollService->getValidEnum();
		$this->assertArrayHasKey('pollType', $enum);
		$this->assertArrayHasKey('access', $enum);
		$this->assertArrayHasKey('showResults', $enum);
		$this->assertContains(Poll::TYPE_TEXT, $enum['pollType']);
		$this->assertContains(Poll::TYPE_DATE, $enum['pollType']);
	}
}
