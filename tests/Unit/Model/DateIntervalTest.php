<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Model;

use OCA\Polls\Model\DateInterval;
use OCA\Polls\Model\DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DateIntervalTest extends TestCase {
	// --- Constructor: string input ---

	public function testConstructorWithISO8601DayString(): void {
		$interval = new DateInterval('P1D');
		$this->assertSame(1, $interval->d);
		$this->assertSame(0, $interval->h);
		$this->assertSame(0, $interval->s);
	}

	public function testConstructorWithISO8601HourString(): void {
		$interval = new DateInterval('PT1H');
		$this->assertSame(1, $interval->h);
		$this->assertSame(0, $interval->d);
	}

	public function testConstructorWithISO8601ComplexString(): void {
		$interval = new DateInterval('P2DT3H');
		$this->assertSame(2, $interval->d);
		$this->assertSame(3, $interval->h);
	}

	// --- Constructor: int input (multiples of 86400 treated as days) ---

	public function testConstructorWithOneDayInSeconds(): void {
		$interval = new DateInterval(86400);
		$this->assertSame(1, $interval->d);
		$this->assertSame(0, $interval->h);
		$this->assertSame(0, $interval->i);
		$this->assertSame(0, $interval->s);
	}

	public function testConstructorWithThreeDaysInSeconds(): void {
		$interval = new DateInterval(3 * 86400);
		$this->assertSame(3, $interval->d);
	}

	// --- Constructor: int input (not a multiple of 86400 treated as seconds) ---

	public function testConstructorWithSecondsNotDivisibleByDay(): void {
		$interval = new DateInterval(3600);
		$this->assertSame(3600, $interval->s);
		$this->assertSame(0, $interval->d);
		$this->assertSame(0, $interval->h);
	}

	public function testConstructorWithSingleSecond(): void {
		$interval = new DateInterval(1);
		$this->assertSame(1, $interval->s);
	}

	// --- Constructor: zero and null yield zero interval ---

	public function testConstructorWithZeroIntYieldsZeroInterval(): void {
		$interval = new DateInterval(0);
		$this->assertTrue($interval->isZeroDuration());
	}

	public function testConstructorWithNullYieldsZeroInterval(): void {
		$interval = new DateInterval(null);
		$this->assertTrue($interval->isZeroDuration());
	}

	// --- Constructor: copy from existing DateInterval ---

	public function testConstructorWithDateIntervalCopiesValues(): void {
		$original = new DateInterval('P2DT3H');
		$copy = new DateInterval($original);
		$this->assertSame($original->d, $copy->d);
		$this->assertSame($original->h, $copy->h);
	}

	// --- isZeroDuration ---

	public function testIsZeroDurationTrueForNullInput(): void {
		$interval = new DateInterval(null);
		$this->assertTrue($interval->isZeroDuration());
	}

	public function testIsZeroDurationTrueForZeroInt(): void {
		$interval = new DateInterval(0);
		$this->assertTrue($interval->isZeroDuration());
	}

	public function testIsZeroDurationFalseForOneDay(): void {
		$interval = new DateInterval('P1D');
		$this->assertFalse($interval->isZeroDuration());
	}

	public function testIsZeroDurationFalseForOneHour(): void {
		$interval = new DateInterval('PT1H');
		$this->assertFalse($interval->isZeroDuration());
	}

	// --- getISO ---

	public function testGetISOReturnsNullForZeroInterval(): void {
		$interval = new DateInterval(null);
		$this->assertNull($interval->getISO());
	}

	public function testGetISOReturnsStringForNonZeroInterval(): void {
		$interval = new DateInterval('P1D');
		$iso = $interval->getISO();
		$this->assertNotNull($iso);
		$this->assertStringStartsWith('P', $iso);
	}

	public function testGetISOReturnsStringForTimeInterval(): void {
		$interval = new DateInterval('PT2H');
		$this->assertNotNull($interval->getISO());
	}

	// --- isDayInterval ---

	public function testIsDayIntervalTrueForP1D(): void {
		$interval = new DateInterval('P1D');
		$this->assertTrue($interval->isDayInterval());
	}

	public function testIsDayIntervalTrueForMultipleDays(): void {
		$interval = new DateInterval('P5D');
		$this->assertTrue($interval->isDayInterval());
	}

	public function testIsDayIntervalTrueForIntOneDayInSeconds(): void {
		$interval = new DateInterval(86400);
		$this->assertTrue($interval->isDayInterval());
	}

	public function testIsDayIntervalFalseForHoursOnly(): void {
		$interval = new DateInterval('PT1H');
		$this->assertFalse($interval->isDayInterval());
	}

	public function testIsDayIntervalFalseForMixedDayAndHour(): void {
		$interval = new DateInterval('P1DT1H');
		$this->assertFalse($interval->isDayInterval());
	}

	public function testIsDayIntervalFalseForZeroInterval(): void {
		$interval = new DateInterval(null);
		$this->assertFalse($interval->isDayInterval());
	}

	// --- getSeconds ---

	public function testGetSecondsForOneDay(): void {
		$interval = new DateInterval('P1D');
		$base = new DateTimeImmutable('2024-06-15T00:00:00+00:00');
		$this->assertSame(86400, $interval->getSeconds($base));
	}

	public function testGetSecondsForOneHour(): void {
		$interval = new DateInterval('PT1H');
		$base = new DateTimeImmutable('2024-06-15T00:00:00+00:00');
		$this->assertSame(3600, $interval->getSeconds($base));
	}

	public function testGetSecondsForThreeDays(): void {
		$interval = new DateInterval('P3D');
		$base = new DateTimeImmutable('2024-06-15T00:00:00+00:00');
		$this->assertSame(3 * 86400, $interval->getSeconds($base));
	}

	public function testGetSecondsForZeroInterval(): void {
		$interval = new DateInterval(null);
		$base = new DateTimeImmutable('2024-06-15T00:00:00+00:00');
		$this->assertSame(0, $interval->getSeconds($base));
	}

	public function testGetSecondsFromIntConstructorMatchesISO(): void {
		$fromInt = new DateInterval(86400);
		$fromISO = new DateInterval('P1D');
		$base = new DateTimeImmutable('2024-06-15T00:00:00+00:00');
		$this->assertSame($fromISO->getSeconds($base), $fromInt->getSeconds($base));
	}
}
