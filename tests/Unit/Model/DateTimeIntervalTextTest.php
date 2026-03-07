<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Model;

use OCA\Polls\Model\DateInterval;
use OCA\Polls\Model\DateTimeImmutable;
use OCA\Polls\Model\DateTimeIntervalText;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;

class DateTimeIntervalTextTest extends TestCase {
	private IL10N $l10n;

	protected function setUp(): void {
		parent::setUp();

		// Mock IL10N to return predictable formatted strings based on type:
		//   'date'     → 'Y-m-d'
		//   'time'     → 'H:i'
		//   'datetime' → 'Y-m-d H:i'
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('l')
			->willReturnCallback(static function (string $type, $data): string {
				if (!$data instanceof \DateTime) {
					return '';
				}
				return match ($type) {
					'date' => $data->format('Y-m-d'),
					'time' => $data->format('H:i'),
					default => $data->format('Y-m-d H:i'),
				};
			});
	}

	private function makeSubject(): DateTimeIntervalText {
		return new DateTimeIntervalText($this->l10n);
	}

	// --- Getters ---

	public function testGetDateTimeReturnsSetStart(): void {
		$subject = $this->makeSubject();
		$start = new DateTimeImmutable('2024-05-10T10:00:00+00:00');
		$subject->setStartDateTime($start);
		$this->assertSame($start->getTimestamp(), $subject->getDateTime()->getTimestamp());
	}

	public function testGetIntervalReturnsSetInterval(): void {
		$subject = $this->makeSubject();
		$interval = new DateInterval('PT2H');
		$subject->setInterval($interval);
		$this->assertSame($interval->h, $subject->getInterval()->h);
	}

	public function testGetEndDateTimeAddsIntervalToStart(): void {
		$subject = $this->makeSubject();
		$start = new DateTimeImmutable('2024-05-10T10:00:00+00:00');
		$interval = new DateInterval('PT2H');
		$subject->setStartDateTime($start);
		$subject->setInterval($interval);
		$end = $subject->getEndDateTime();
		$this->assertSame($start->getTimestamp() + 7200, $end->getTimestamp());
	}

	// --- getLocalizedDateTimeString: branch 1 ---
	// Zero duration, NOT start of day → format='datetime'

	public function testZeroDurationNotStartOfDayReturnsSingleDatetime(): void {
		$subject = $this->makeSubject();
		$subject->setStartDateTime(new DateTimeImmutable('2024-01-15T10:30:00+00:00'));
		$subject->setInterval(new DateInterval(null));

		$result = $subject->getLocalizedDateTimeString();

		$this->assertSame('2024-01-15 10:30', $result);
	}

	// --- getLocalizedDateTimeString: branch 2 ---
	// Zero duration, IS start of day → format='date'

	public function testZeroDurationStartOfDayReturnsSingleDate(): void {
		$subject = $this->makeSubject();
		$subject->setStartDateTime(new DateTimeImmutable('2024-01-15T00:00:00+00:00'));
		$subject->setInterval(new DateInterval(null));

		$result = $subject->getLocalizedDateTimeString();

		$this->assertSame('2024-01-15', $result);
	}

	// --- getLocalizedDateTimeString: branch 3 ---
	// Day interval, IS start of day, same day after subtracting P1D → single date

	public function testSingleDayFullDayEventReturnsSingleDate(): void {
		$subject = $this->makeSubject();
		$subject->setStartDateTime(new DateTimeImmutable('2024-01-15T00:00:00+00:00'));
		$subject->setInterval(new DateInterval('P1D'));

		$result = $subject->getLocalizedDateTimeString();

		$this->assertSame('2024-01-15', $result);
	}

	// --- getLocalizedDateTimeString: branch 4 ---
	// Day interval, IS start of day, spanning multiple days → "start – end"

	public function testMultiDayFullDayEventReturnsDateRange(): void {
		$subject = $this->makeSubject();
		$subject->setStartDateTime(new DateTimeImmutable('2024-01-15T00:00:00+00:00'));
		$subject->setInterval(new DateInterval('P3D'));

		$result = $subject->getLocalizedDateTimeString();

		// end = 2024-01-18, minus P1D = 2024-01-17 (inclusive end)
		$this->assertSame('2024-01-15 – 2024-01-17', $result);
	}

	public function testTwoDayFullDayEventReturnsDateRange(): void {
		$subject = $this->makeSubject();
		$subject->setStartDateTime(new DateTimeImmutable('2024-01-15T00:00:00+00:00'));
		$subject->setInterval(new DateInterval('P2D'));

		$result = $subject->getLocalizedDateTimeString();

		// end = 2024-01-17, minus P1D = 2024-01-16
		$this->assertSame('2024-01-15 – 2024-01-16', $result);
	}

	// --- getLocalizedDateTimeString: branch 5 ---
	// Time range on the same calendar day → "datetime – time"

	public function testSameDayTimeRangeReturnsDatetimeThenTimeOnly(): void {
		$subject = $this->makeSubject();
		$subject->setStartDateTime(new DateTimeImmutable('2024-01-15T10:00:00+00:00'));
		$subject->setInterval(new DateInterval('PT2H'));

		$result = $subject->getLocalizedDateTimeString();

		$this->assertSame('2024-01-15 10:00 – 12:00', $result);
	}

	public function testSameDayTimeRangeEndingAtMidnightIsNotSameDay(): void {
		$subject = $this->makeSubject();
		// 22:00 + 2h = 00:00 next day → cross-day
		$subject->setStartDateTime(new DateTimeImmutable('2024-01-15T22:00:00+00:00'));
		$subject->setInterval(new DateInterval('PT2H'));

		$result = $subject->getLocalizedDateTimeString();

		$this->assertSame('2024-01-15 22:00 – 2024-01-16 00:00', $result);
	}

	// --- getLocalizedDateTimeString: branch 6 ---
	// Time range spanning midnight → "datetime – datetime"

	public function testCrossDayTimeRangeReturnsTwoDatetimes(): void {
		$subject = $this->makeSubject();
		$subject->setStartDateTime(new DateTimeImmutable('2024-01-15T22:00:00+00:00'));
		$subject->setInterval(new DateInterval('PT4H'));

		$result = $subject->getLocalizedDateTimeString();

		// start: 2024-01-15 22:00, end: 2024-01-16 02:00
		$this->assertSame('2024-01-15 22:00 – 2024-01-16 02:00', $result);
	}

	// --- setStartDateTime / setInterval return $this for chaining ---

	public function testFluentInterfaceForSetStartDateTime(): void {
		$subject = $this->makeSubject();
		$returned = $subject->setStartDateTime(new DateTimeImmutable('2024-01-01T00:00:00+00:00'));
		$this->assertSame($subject, $returned);
	}

	public function testFluentInterfaceForSetInterval(): void {
		$subject = $this->makeSubject();
		$returned = $subject->setInterval(new DateInterval('P1D'));
		$this->assertSame($subject, $returned);
	}
}
