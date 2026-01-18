<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Polls\Helper;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Exception;

abstract class DateHelper {
	/**
	 * Get DateTimeImmutable from various input types and apply time zone if provided
	 *
	 * @param null|int|string|DateTimeImmutable $dateValue Date representation as timestamp, string, or DateTimeImmutable.
	 * @param null|non-empty-string|DateTimeZone $timeZone Optional time zone as string or DateTimeZone
	 * @return null|DateTimeImmutable DateTimeImmutable object or null if input is null
	 */
	public static function getDateTimeImmutable(
		null|int|string|DateTimeImmutable $dateValue,
		null|string|DateTimeZone $timeZone = null,
	): ?DateTimeImmutable {

		if ($dateValue instanceof DateTimeImmutable) {
			// Already a DateTimeImmutable
			$dateTime = $dateValue;
		} elseif (is_int($dateValue)) {
			// Treat as Unix timestamp (in seconds) and create DateTimeImmutable
			$dateTime = (new DateTimeImmutable())->setTimestamp($dateValue);
		} elseif (is_string($dateValue)) {
			// Treat as ISO 8601 date string and create DateTimeImmutable
			$dateTime = new DateTimeImmutable($dateValue);
		} else {
			// Null input, return null
			return null;
		}

		try {
			if (is_string($timeZone)) {
				// Convert string to DateTimeZone
				$timeZone = new DateTimeZone($timeZone);
			}

			if ($timeZone instanceof DateTimeZone) {
				// Apply time zone
				return $dateTime->setTimezone($timeZone);
			}
		} catch (Exception $e) {
			// Invalid time zone, ignore and return original dateTime
		}

		return $dateTime;
	}

	/**
	 * Convert duration in seconds to DateInterval
	 *
	 * Note: We always need an offset date to correctly handle month/year durations.
	 * To avoid issues with Daylight Saving Time changes, additionally provide a time zone.
	 *
	 * @param null|int|string|DateInterval $duration Duration in seconds
	 * @param null|int|non-empty-string|DateTimeImmutable $offsetDate Offset date for context
	 * @param null|non-empty-string|DateTimeZone $timeZone Optional time zone as string or DateTimeZone
	 * @return null|DateInterval Normalized DateInterval object or null if duration is null or invalid
	 */
	public static function getDateInterval(
		null|int|string|DateInterval $duration,
		null|int|string|DateTimeImmutable $offsetDate,
		null|string|DateTimeZone $timeZone = null,
	): ?DateInterval {
		if ($duration === null) {
			// Handle null duration
			return null;
		}
		if ($duration instanceof DateInterval) {
			// If already a DateInterval, return as is
			return $duration;
		}

		if (is_string($duration)) {
			// If duration is a string, assume it's an ISO 8601 duration and create DateInterval directly
			return new DateInterval($duration);
		}

		$baseDate = self::getDateTimeImmutable($offsetDate, $timeZone);

		if ($baseDate === null) {
			// If base date is null, we cannot compute interval
			return null;
		}

		if ($duration % 86400 === 0) {
			// If numeric duration is set to full days, return a DateInterval with only days
			$days = (int)($duration / 86400);
			return new DateInterval('P' . $days . 'D');
		}

		// For other durations, compute end date and get the interval
		$endDate = $baseDate->add(new DateInterval('PT' . $duration . 'S'));
		return $baseDate->diff($endDate);
	}

	public static function dateIntervalToSeconds(?DateInterval $interval, DateTimeImmutable $baseDate): ?int {
		if ($interval === null) {
			return null;
		}
		$endDate = $baseDate->add($interval);
		return (int)($endDate->getTimestamp() - $baseDate->getTimestamp());
	}
	/**
	 * Get compressed ISO 8601 duration string from DateInterval
	 *
	 * Only return non-zero parts of the interval.
	 *
	 * Possible alternative implementation found at: https://stackoverflow.com/questions/33787039/format-dateinterval-as-iso8601/42598056#42598056
	 *
	 * @param null|DateInterval $interval DateInterval to convert
	 * @return null|non-empty-string Compressed ISO 8601 duration string
	 */
	public static function dateIntervalToIso(?DateInterval $interval): ?string {
		if ($interval === null) {
			return null;
		}
		$dateParts = [];
		if ($interval->y !== 0) {
			$dateParts[] = $interval->y . 'Y';
		}
		if ($interval->m !== 0) {
			$dateParts[] = $interval->m . 'M';
		}
		if ($interval->d !== 0) {
			$dateParts[] = $interval->d . 'D';
		}

		$timeParts = [];

		if ($interval->h !== 0) {
			$timeParts[] = $interval->h . 'H';
		}
		if ($interval->i !== 0) {
			$timeParts[] = $interval->i . 'M';
		}
		if ($interval->s !== 0) {
			$timeParts[] = $interval->s . 'S';
		}

		$result = 'P' . implode('', $dateParts);
		if (!empty($timeParts)) {
			$result = $result . 'T' . implode('', $timeParts);
		}

		if ($result === 'P') {
			return 'PT0S'; // For zero duration
		}

		return $result;
	}

	/**
	 * Convert duration in seconds to normalized and compressed ISO 8601 duration string
	 *
	 * @param int $duration Duration in seconds
	 * @param int|non-empty-string|DateTimeImmutable $offsetDate Offset date for context
	 * @param null|DateTimeZone|non-empty-string $timeZone Optional time zone
	 * @return null|non-empty-string ISO 8601 duration string
	 */
	public static function durationToIso(
		int $duration,
		int|string|DateTimeImmutable $offsetDate,
		null|DateTimeZone|string $timeZone = null,
	) : ?string {
		$interval = self::getDateInterval($duration, $offsetDate, $timeZone);
		return self::dateIntervalToIso($interval);
	}

}
