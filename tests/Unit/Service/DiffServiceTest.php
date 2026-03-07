<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Service\DiffService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * DiffService receives Entity/Poll objects and JSON-encodes them internally.
 * We mock Poll (bypassing its complex DI constructor) and control jsonSerialize()
 * to produce known arrays, so every test exercises pure diff logic only.
 */
class DiffServiceTest extends TestCase {
	/** @return Poll&MockObject */
	private function makePoll(array $data): Poll {
		$poll = $this->createMock(Poll::class);
		$poll->method('jsonSerialize')->willReturn($data);
		return $poll;
	}

	// --- Initial state before setComparisonObject ---

	public function testFullDiffIsEmptyBeforeComparison(): void {
		$service = new DiffService($this->makePoll(['title' => 'Test']));
		$this->assertSame([], $service->getFullDiff());
	}

	public function testNewValuesDiffIsEmptyBeforeComparison(): void {
		$service = new DiffService($this->makePoll(['title' => 'Test']));
		$this->assertSame([], $service->getNewValuesDiff());
	}

	// --- Identical objects produce empty diffs ---

	public function testFullDiffIsEmptyForIdenticalObjects(): void {
		$data = ['title' => 'My Poll', 'type' => 'datePoll', 'expire' => 0];
		$service = new DiffService($this->makePoll($data));
		$service->setComparisonObject($this->makePoll($data));

		$this->assertSame([], $service->getFullDiff());
	}

	public function testNewValuesDiffIsEmptyForIdenticalObjects(): void {
		$data = ['title' => 'My Poll', 'type' => 'textPoll'];
		$service = new DiffService($this->makePoll($data));
		$service->setComparisonObject($this->makePoll($data));

		$this->assertSame([], $service->getNewValuesDiff());
	}

	// --- Single scalar field changed ---

	public function testFullDiffDetectsSingleFieldChange(): void {
		$service = new DiffService($this->makePoll(['title' => 'Original']));
		$service->setComparisonObject($this->makePoll(['title' => 'Updated']));

		$diff = $service->getFullDiff();

		$this->assertArrayHasKey('title', $diff);
		$this->assertSame('Original', $diff['title']['old']);
		$this->assertSame('Updated', $diff['title']['new']);
	}

	public function testNewValuesDiffReturnsSingleChangedField(): void {
		$service = new DiffService($this->makePoll(['title' => 'Original']));
		$service->setComparisonObject($this->makePoll(['title' => 'Updated']));

		$this->assertSame(['title' => 'Updated'], $service->getNewValuesDiff());
	}

	// --- Multiple fields changed ---

	public function testFullDiffDetectsMultipleChangedFields(): void {
		$service = new DiffService($this->makePoll([
			'title' => 'Old Title',
			'type' => 'datePoll',
			'expire' => 0,
		]));
		$service->setComparisonObject($this->makePoll([
			'title' => 'New Title',
			'type' => 'textPoll',
			'expire' => 0,
		]));

		$diff = $service->getFullDiff();

		$this->assertArrayHasKey('title', $diff);
		$this->assertArrayHasKey('type', $diff);
		$this->assertArrayNotHasKey('expire', $diff);
		$this->assertSame('New Title', $diff['title']['new']);
		$this->assertSame('textPoll', $diff['type']['new']);
	}

	public function testNewValuesDiffReturnsOnlyChangedFields(): void {
		$service = new DiffService($this->makePoll([
			'title' => 'Old Title',
			'type' => 'datePoll',
			'expire' => 0,
		]));
		$service->setComparisonObject($this->makePoll([
			'title' => 'New Title',
			'type' => 'textPoll',
			'expire' => 0,
		]));

		$newValues = $service->getNewValuesDiff();

		$this->assertArrayHasKey('title', $newValues);
		$this->assertArrayHasKey('type', $newValues);
		$this->assertArrayNotHasKey('expire', $newValues);
		$this->assertSame('New Title', $newValues['title']);
		$this->assertSame('textPoll', $newValues['type']);
	}

	// --- Key added in comparison (not in base) ---

	public function testFullDiffDetectsNewKeyInComparison(): void {
		$service = new DiffService($this->makePoll(['title' => 'Test']));
		$service->setComparisonObject($this->makePoll(['title' => 'Test', 'extra' => 'added']));

		$diff = $service->getFullDiff();

		$this->assertArrayHasKey('extra', $diff);
		$this->assertNull($diff['extra']['old']);
		$this->assertSame('added', $diff['extra']['new']);
	}

	// --- Key removed in comparison (present in base, absent in comparison) ---

	public function testFullDiffDetectsRemovedKeyInComparison(): void {
		$service = new DiffService($this->makePoll(['title' => 'Test', 'extra' => 'was here']));
		$service->setComparisonObject($this->makePoll(['title' => 'Test']));

		$diff = $service->getFullDiff();

		$this->assertArrayHasKey('extra', $diff);
		$this->assertSame('was here', $diff['extra']['old']);
		$this->assertNull($diff['extra']['new']);
	}

	public function testNewValuesDiffForRemovedKeyContainsNull(): void {
		$service = new DiffService($this->makePoll(['title' => 'Test', 'extra' => 'was here']));
		$service->setComparisonObject($this->makePoll(['title' => 'Test']));

		$newValues = $service->getNewValuesDiff();

		$this->assertArrayHasKey('extra', $newValues);
		$this->assertNull($newValues['extra']);
	}

	// --- Nested array changes ---

	public function testFullDiffDetectsNestedFieldChange(): void {
		$service = new DiffService($this->makePoll([
			'configuration' => ['access' => 'private', 'allowComment' => false],
		]));
		$service->setComparisonObject($this->makePoll([
			'configuration' => ['access' => 'open', 'allowComment' => false],
		]));

		$diff = $service->getFullDiff();

		$this->assertArrayHasKey('configuration', $diff);
		$this->assertArrayHasKey('access', $diff['configuration']);
		$this->assertSame('private', $diff['configuration']['access']['old']);
		$this->assertSame('open', $diff['configuration']['access']['new']);
		$this->assertArrayNotHasKey('allowComment', $diff['configuration']);
	}

	public function testNewValuesDiffPreservesNestedStructure(): void {
		$service = new DiffService($this->makePoll([
			'configuration' => ['access' => 'private', 'expire' => 0],
		]));
		$service->setComparisonObject($this->makePoll([
			'configuration' => ['access' => 'open', 'expire' => 0],
		]));

		$newValues = $service->getNewValuesDiff();

		$this->assertArrayHasKey('configuration', $newValues);
		$this->assertArrayHasKey('access', $newValues['configuration']);
		$this->assertSame('open', $newValues['configuration']['access']);
		$this->assertArrayNotHasKey('expire', $newValues['configuration']);
	}

	// --- Recalculation when setComparisonObject is called again ---

	public function testDiffIsRecalculatedOnSubsequentSetComparisonObjectCall(): void {
		$service = new DiffService($this->makePoll(['title' => 'Base']));

		$service->setComparisonObject($this->makePoll(['title' => 'First']));
		$this->assertSame('First', $service->getFullDiff()['title']['new']);

		$service->setComparisonObject($this->makePoll(['title' => 'Second']));
		$this->assertSame('Second', $service->getFullDiff()['title']['new']);
	}

	// --- Type changes ---

	public function testFullDiffDetectsTypeChange(): void {
		$service = new DiffService($this->makePoll(['expire' => 0]));
		$service->setComparisonObject($this->makePoll(['expire' => 1700000000]));

		$diff = $service->getFullDiff();

		$this->assertSame(0, $diff['expire']['old']);
		$this->assertSame(1700000000, $diff['expire']['new']);
	}

	public function testFullDiffDetectsBooleanChange(): void {
		$service = new DiffService($this->makePoll(['allowComment' => false]));
		$service->setComparisonObject($this->makePoll(['allowComment' => true]));

		$diff = $service->getFullDiff();

		$this->assertSame(false, $diff['allowComment']['old']);
		$this->assertSame(true, $diff['allowComment']['new']);
	}
}
