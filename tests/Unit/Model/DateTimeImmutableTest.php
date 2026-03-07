<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Model;

use OCA\Polls\Model\DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class DateTimeImmutableTest extends TestCase {
	// --- Constructor ---

	public function testConstructorWithNullYieldsEpoch(): void {
		$dt = new DateTimeImmutable(null);
		$this->assertSame(0, $dt->getTimestamp());
	}

	public function testConstructorWithZeroIntYieldsEpoch(): void {
		$dt = new DateTimeImmutable(0);
		$this->assertSame(0, $dt->getTimestamp());
	}

	public function testConstructorWithNegativeIntYieldsEpoch(): void {
		$dt = new DateTimeImmutable(-1);
		$this->assertSame(0, $dt->getTimestamp());
	}

	public function testConstructorWithEmptyStringYieldsEpoch(): void {
		$dt = new DateTimeImmutable('');
		$this->assertSame(0, $dt->getTimestamp());
	}

	public function testConstructorWithPositiveIntUsesUnixTimestamp(): void {
		$timestamp = 1700000000;
		$dt = new DateTimeImmutable($timestamp);
		$this->assertSame($timestamp, $dt->getTimestamp());
	}

	public function testConstructorWithISOStringParsesCorrectly(): void {
		$dt = new DateTimeImmutable('2024-03-15T14:30:00+00:00');
		$this->assertSame('2024-03-15', $dt->format('Y-m-d'));
		$this->assertSame('14:30:00', $dt->format('H:i:s'));
	}

	public function testConstructorWithDateTimeImmutableCopiesTimestamp(): void {
		$original = new DateTimeImmutable('2024-06-01T08:00:00+00:00');
		$copy = new DateTimeImmutable($original);
		$this->assertSame($original->getTimestamp(), $copy->getTimestamp());
	}

	// --- isStartOfDay ---

	public function testIsStartOfDayTrueAtMidnight(): void {
		$dt = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
		$this->assertTrue($dt->isStartOfDay());
	}

	public function testIsStartOfDayFalseAtNoon(): void {
		$dt = new DateTimeImmutable('2024-01-01T12:00:00+00:00');
		$this->assertFalse($dt->isStartOfDay());
	}

	public function testIsStartOfDayFalseOneSecondPastMidnight(): void {
		$dt = new DateTimeImmutable('2024-01-01T00:00:01+00:00');
		$this->assertFalse($dt->isStartOfDay());
	}

	// --- getISO ---

	public function testGetISOReturnsNullForEpoch(): void {
		$dt = new DateTimeImmutable(null);
		$this->assertNull($dt->getISO());
	}

	public function testGetISOReturnsNullForZeroTimestamp(): void {
		$dt = new DateTimeImmutable(0);
		$this->assertNull($dt->getISO());
	}

	public function testGetISOReturnsStringForValidDate(): void {
		$dt = new DateTimeImmutable('2024-03-15T14:30:00+00:00');
		$iso = $dt->getISO();
		$this->assertNotNull($iso);
		$this->assertStringContainsString('2024-03-15', $iso);
		$this->assertStringContainsString('14:30:00', $iso);
	}

	public function testGetISOReturnsStringForPositiveTimestamp(): void {
		$dt = new DateTimeImmutable(1700000000);
		$this->assertNotNull($dt->getISO());
	}

	// --- getISODate ---

	public function testGetISODateReturnsNullForEpoch(): void {
		$dt = new DateTimeImmutable(null);
		$this->assertNull($dt->getISODate());
	}

	public function testGetISODateReturnsDateStringForValidDate(): void {
		$dt = new DateTimeImmutable('2024-03-15T14:30:00+00:00');
		$this->assertSame('2024-03-15', $dt->getISODate());
	}

	public function testGetISODateReturnsOnlyDatePortion(): void {
		$dt = new DateTimeImmutable('2024-12-31T23:59:59+00:00');
		$this->assertSame('2024-12-31', $dt->getISODate());
	}
}
