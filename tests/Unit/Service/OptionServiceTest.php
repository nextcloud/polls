<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Service;

use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Exceptions\InsufficientAttributesException;
use OCA\Polls\Model\SimpleOption;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\ISession;
use OCP\Server;

class OptionServiceTest extends UnitTestCase {
	private OptionService $optionService;
	private OptionMapper $optionMapper;
	private PollMapper $pollMapper;
	private ISession $session;

	private Poll $textPoll;
	private Poll $datePoll;
	private Option $textOption;

	protected function setUp(): void {
		parent::setUp();
		$this->session = Server::get(ISession::class);
		$this->session->set('ncPollsUserId', 'admin');

		$this->optionService = Server::get(OptionService::class);
		$this->optionMapper = Server::get(OptionMapper::class);
		$this->pollMapper = Server::get(PollMapper::class);

		// Text poll owned by admin, private, open
		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll->setOwner('admin');
		$poll->setType(Poll::TYPE_TEXT);
		$poll->setAccess(Poll::ACCESS_PRIVATE);
		$poll->setExpire(0);
		$this->textPoll = $this->pollMapper->insert($poll);

		// Date poll owned by admin, private, open
		$datePoll = $this->fm->instance('OCA\Polls\Db\Poll');
		$datePoll->setOwner('admin');
		$datePoll->setType(Poll::TYPE_DATE);
		$datePoll->setAccess(Poll::ACCESS_PRIVATE);
		$datePoll->setExpire(0);
		$this->datePoll = $this->pollMapper->insert($datePoll);

		// Pre-existing text option for update/delete/confirm/reorder tests
		$this->textOption = $this->optionService->add(
			$this->textPoll->getId(),
			(new SimpleOption())->setText('Initial option')
		);
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Options are deleted when their poll is deleted
		try {
			$this->pollMapper->delete($this->textPoll);
		} catch (\Exception $e) {
		}
		try {
			$this->pollMapper->delete($this->datePoll);
		} catch (\Exception $e) {
		}
	}

	// --- add ---

	public function testAddTextOptionToTextPoll(): void {
		$option = $this->optionService->add(
			$this->textPoll->getId(),
			(new SimpleOption())->setText('New Option')
		);
		$this->assertInstanceOf(Option::class, $option);
		$this->assertSame('New Option', $option->getPollOptionText());
		$this->assertSame($this->textPoll->getId(), $option->getPollId());
	}

	public function testAddDateOptionToDatePoll(): void {
		$timestamp = strtotime('2027-06-15 12:00:00 UTC');
		$option = $this->optionService->add(
			$this->datePoll->getId(),
			(new SimpleOption())->setDateTime($timestamp)
		);
		$this->assertInstanceOf(Option::class, $option);
		$this->assertSame($this->datePoll->getId(), $option->getPollId());
		$this->assertSame($timestamp, $option->getTimestamp());
	}

	// --- addBulk ---

	public function testAddBulkAddsMultipleOptions(): void {
		$bulkText = implode(PHP_EOL, ['Bulk A', 'Bulk B', 'Bulk C']);
		$options = $this->optionService->addBulk($this->textPoll->getId(), $bulkText);
		// setUp created 1 option; addBulk adds 3 more → at least 4
		$this->assertGreaterThanOrEqual(4, count($options));
	}

	public function testAddBulkDeduplicates(): void {
		$countBefore = count($this->optionService->list($this->textPoll->getId()));
		$bulkText = implode(PHP_EOL, ['Unique One', 'Unique One']);
		$this->optionService->addBulk($this->textPoll->getId(), $bulkText);
		$countAfter = count($this->optionService->list($this->textPoll->getId()));
		// array_unique in addBulk means only 1 new option added despite 2 lines
		$this->assertSame($countBefore + 1, $countAfter);
	}

	// --- list ---

	public function testListReturnsOptionsForPoll(): void {
		$options = $this->optionService->list($this->textPoll->getId());
		$this->assertNotEmpty($options);
		foreach ($options as $option) {
			$this->assertSame($this->textPoll->getId(), $option->getPollId());
		}
	}

	public function testListReturnsEmptyArrayForPollWithNoOptions(): void {
		$options = $this->optionService->list($this->datePoll->getId());
		$this->assertIsArray($options);
		$this->assertEmpty($options);
	}

	// --- update ---

	public function testUpdateTextOptionChangesText(): void {
		$updated = $this->optionService->update($this->textOption->getId(), 'Updated Text');
		$this->assertSame('Updated Text', $updated->getPollOptionText());
	}

	public function testUpdateTextOptionThrowsOnEmptyText(): void {
		$this->expectException(InsufficientAttributesException::class);
		$this->optionService->update($this->textOption->getId(), '');
	}

	// --- delete / restore ---

	public function testDeleteSetsDeletedTimestamp(): void {
		$deleted = $this->optionService->delete($this->textOption->getId());
		$this->assertGreaterThan(0, $deleted->getDeleted());
	}

	public function testRestoreClearsDeletedTimestamp(): void {
		$this->optionService->delete($this->textOption->getId());
		$restored = $this->optionService->delete($this->textOption->getId(), true);
		$this->assertSame(0, $restored->getDeleted());
	}

	// --- confirm ---

	public function testConfirmOptionOnExpiredPoll(): void {
		// Poll must be expired for confirm permission
		$this->textPoll->setExpire(time() - 3600);
		$this->pollMapper->update($this->textPoll);

		$confirmed = $this->optionService->confirm($this->textOption->getId());
		$this->assertGreaterThan(0, $confirmed->getConfirmed());

		// Restore: re-open the poll for tearDown
		$this->textPoll->setExpire(0);
		$this->pollMapper->update($this->textPoll);
	}

	public function testConfirmTogglesConfirmation(): void {
		$this->textPoll->setExpire(time() - 3600);
		$this->pollMapper->update($this->textPoll);

		$this->optionService->confirm($this->textOption->getId()); // confirm
		$unconfirmed = $this->optionService->confirm($this->textOption->getId()); // toggle back
		$this->assertSame(0, $unconfirmed->getConfirmed());

		$this->textPoll->setExpire(0);
		$this->pollMapper->update($this->textPoll);
	}

	// --- reorder ---

	public function testReorderChangesOptionOrder(): void {
		$second = $this->optionService->add(
			$this->textPoll->getId(),
			(new SimpleOption())->setText('Second Option')
		);

		// Swap order: second first, initial second
		$reordered = $this->optionService->reorder($this->textPoll->getId(), [
			['id' => $second->getId()],
			['id' => $this->textOption->getId()],
		]);

		$this->assertCount(2, $reordered);
		$orderById = [];
		foreach ($reordered as $opt) {
			$orderById[$opt->getId()] = $opt->getOrder();
		}
		$this->assertSame(1, $orderById[$second->getId()]);
		$this->assertSame(2, $orderById[$this->textOption->getId()]);
	}
}
