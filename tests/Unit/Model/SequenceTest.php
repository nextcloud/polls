<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Model;

use OCA\Polls\Model\DateTimeImmutable;
use OCA\Polls\Model\Sequence;
use OCA\Polls\Model\SequenceUnit;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase {
	// --- Getters ---

	public function testGetUnitReturnsConstructedUnit(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_DAY);
		$seq = new Sequence($unit, 2, 5);
		$this->assertSame($unit, $seq->getUnit());
	}

	public function testGetStepWidthReturnsConstructedValue(): void {
		$seq = new Sequence(new SequenceUnit(SequenceUnit::REPETITION_DAY), 3, 7);
		$this->assertSame(3, $seq->getStepWidth());
	}

	public function testGetRepetitionsReturnsConstructedValue(): void {
		$seq = new Sequence(new SequenceUnit(SequenceUnit::REPETITION_DAY), 1, 10);
		$this->assertSame(10, $seq->getRepetitions());
	}

	// --- jsonSerialize ---

	public function testJsonSerializeContainsRequiredKeys(): void {
		$seq = new Sequence(new SequenceUnit(SequenceUnit::REPETITION_WEEK), 1, 4);
		$json = $seq->jsonSerialize();

		$this->assertArrayHasKey('unit', $json);
		$this->assertArrayHasKey('stepWidth', $json);
		$this->assertArrayHasKey('repetitions', $json);
	}

	public function testJsonSerializeValuesMatchConstructorInput(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_MONTH);
		$seq = new Sequence($unit, 2, 6);
		$json = $seq->jsonSerialize();

		$this->assertSame(2, $json['stepWidth']);
		$this->assertSame(6, $json['repetitions']);
	}

	public function testJsonSerializeUnitIsSerialized(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_WEEK);
		$seq = new Sequence($unit, 1, 3);
		$json = $seq->jsonSerialize();

		// The 'unit' value is the SequenceUnit itself (JsonSerializable), so it must be an array
		$this->assertIsArray($json['unit']);
		$this->assertSame('week', $json['unit']['id']);
	}

	// --- fromArray ---

	public function testFromArrayWithNullReturnsDefaultSequence(): void {
		$seq = Sequence::fromArray(null);
		$this->assertSame(SequenceUnit::REPETITION_DAY, $seq->getUnit()->getId());
		$this->assertSame(1, $seq->getStepWidth());
		$this->assertSame(0, $seq->getRepetitions());
	}

	public function testFromArrayWithValidDataReturnsCorrectSequence(): void {
		$data = [
			'unit' => ['id' => SequenceUnit::REPETITION_WEEK],
			'stepWidth' => 2,
			'repetitions' => 4,
		];
		$seq = Sequence::fromArray($data);

		$this->assertSame(SequenceUnit::REPETITION_WEEK, $seq->getUnit()->getId());
		$this->assertSame(2, $seq->getStepWidth());
		$this->assertSame(4, $seq->getRepetitions());
	}

	public function testFromArrayFallsBackToDefaultUnitWhenMissing(): void {
		$seq = Sequence::fromArray(['stepWidth' => 3, 'repetitions' => 5]);
		$this->assertSame(SequenceUnit::REPETITION_DAY, $seq->getUnit()->getId());
		$this->assertSame(3, $seq->getStepWidth());
		$this->assertSame(5, $seq->getRepetitions());
	}

	public function testFromArrayFallsBackToDefaultStepWidthWhenMissing(): void {
		$seq = Sequence::fromArray(['unit' => ['id' => 'month'], 'repetitions' => 2]);
		$this->assertSame(1, $seq->getStepWidth());
	}

	public function testFromArrayFallsBackToDefaultRepetitionsWhenMissing(): void {
		$seq = Sequence::fromArray(['unit' => ['id' => 'day'], 'stepWidth' => 1]);
		$this->assertSame(0, $seq->getRepetitions());
	}

	// --- getOccurence ---

	public function testGetOccurenceAtIndexZeroReturnsSameDate(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_DAY);
		$seq = new Sequence($unit, 1, 7);
		$base = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
		$seq->setBaseDateTime($base);

		$occurrence = $seq->getOccurence(0);
		$this->assertSame('2024-01-01', $occurrence->getISODate());
	}

	public function testGetOccurenceAtIndexOneReturnsPlusOneStep(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_DAY);
		$seq = new Sequence($unit, 1, 7);
		$base = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
		$seq->setBaseDateTime($base);

		$occurrence = $seq->getOccurence(1);
		$this->assertSame('2024-01-02', $occurrence->getISODate());
	}

	public function testGetOccurenceWithStepWidthThree(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_DAY);
		$seq = new Sequence($unit, 3, 7);
		$base = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
		$seq->setBaseDateTime($base);

		// index=2: 2 * 3 = 6 days later
		$occurrence = $seq->getOccurence(2);
		$this->assertSame('2024-01-07', $occurrence->getISODate());
	}

	public function testGetOccurenceReturnsDateTimeImmutableInstance(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_DAY);
		$seq = new Sequence($unit, 1, 3);
		$base = new DateTimeImmutable('2024-06-01T00:00:00+00:00');
		$seq->setBaseDateTime($base);

		$occurrence = $seq->getOccurence(1);
		$this->assertInstanceOf(DateTimeImmutable::class, $occurrence);
	}

	public function testGetOccurenceWithMonthUnit(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_MONTH);
		$seq = new Sequence($unit, 1, 12);
		$base = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
		$seq->setBaseDateTime($base);

		// index=3: 3 months later
		$occurrence = $seq->getOccurence(3);
		$this->assertSame('2024-04-01', $occurrence->getISODate());
	}
}
