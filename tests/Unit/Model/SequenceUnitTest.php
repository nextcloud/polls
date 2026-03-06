<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Model;

use OCA\Polls\Model\SequenceUnit;
use PHPUnit\Framework\TestCase;

class SequenceUnitTest extends TestCase {
	// --- getId ---

	public function testGetIdReturnsDayConstant(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_DAY);
		$this->assertSame(SequenceUnit::REPETITION_DAY, $unit->getId());
	}

	public function testGetIdReturnsWeekConstant(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_WEEK);
		$this->assertSame(SequenceUnit::REPETITION_WEEK, $unit->getId());
	}

	// --- jsonSerialize: structure ---

	/**
	 * @dataProvider unitProvider
	 */
	public function testJsonSerializeContainsRequiredKeys(string $unitId): void {
		$unit = new SequenceUnit($unitId);
		$json = $unit->jsonSerialize();

		$this->assertArrayHasKey('id', $json);
		$this->assertArrayHasKey('luxonUnit', $json);
		$this->assertArrayHasKey('name', $json);
	}

	/**
	 * @dataProvider unitProvider
	 */
	public function testJsonSerializeIdMatchesConstructorInput(string $unitId): void {
		$unit = new SequenceUnit($unitId);
		$this->assertSame($unitId, $unit->jsonSerialize()['id']);
	}

	// --- jsonSerialize: concrete expected values ---

	public function testJsonSerializeForYear(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_YEAR);
		$json = $unit->jsonSerialize();
		$this->assertSame('year', $json['id']);
		$this->assertSame('years', $json['luxonUnit']);
		$this->assertSame('Year', $json['name']);
	}

	public function testJsonSerializeForMonth(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_MONTH);
		$json = $unit->jsonSerialize();
		$this->assertSame('month', $json['id']);
		$this->assertSame('months', $json['luxonUnit']);
		$this->assertSame('Month', $json['name']);
	}

	public function testJsonSerializeForWeek(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_WEEK);
		$json = $unit->jsonSerialize();
		$this->assertSame('week', $json['id']);
		$this->assertSame('weeks', $json['luxonUnit']);
		$this->assertSame('Week', $json['name']);
	}

	public function testJsonSerializeForDay(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_DAY);
		$json = $unit->jsonSerialize();
		$this->assertSame('day', $json['id']);
		$this->assertSame('days', $json['luxonUnit']);
		$this->assertSame('Day', $json['name']);
	}

	public function testJsonSerializeForHour(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_HOUR);
		$json = $unit->jsonSerialize();
		$this->assertSame('hour', $json['id']);
		$this->assertSame('hours', $json['luxonUnit']);
		$this->assertSame('Hour', $json['name']);
	}

	public function testJsonSerializeForMinute(): void {
		$unit = new SequenceUnit(SequenceUnit::REPETITION_MINUTE);
		$json = $unit->jsonSerialize();
		$this->assertSame('minute', $json['id']);
		$this->assertSame('minutes', $json['luxonUnit']);
		$this->assertSame('Minute', $json['name']);
	}

	public static function unitProvider(): array {
		return [
			'year'   => [SequenceUnit::REPETITION_YEAR],
			'month'  => [SequenceUnit::REPETITION_MONTH],
			'week'   => [SequenceUnit::REPETITION_WEEK],
			'day'    => [SequenceUnit::REPETITION_DAY],
			'hour'   => [SequenceUnit::REPETITION_HOUR],
			'minute' => [SequenceUnit::REPETITION_MINUTE],
		];
	}
}
