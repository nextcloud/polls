<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Polls\Helper;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use OCA\Polls\Exceptions\InsufficientAttributesException;
use OCP\IL10N;

/**
 * @psalm-suppress UnusedClass
 */
abstract class DateHelper {
	/**
	 * Get DateTimeImmutable from various input types
	 *
	 * @param null|int|string|DateTimeImmutable $dateValue Date representation as timestamp, string, or DateTimeImmutable.
	 * @return null|DateTimeImmutable DateTimeImmutable object
	 * @throws Exception If the input value is invalid and cannot be converted to a DateTimeImmutable
	 */
	public static function getDateTimeImmutable(
		null|int|string|DateTimeImmutable $dateValue,
	): ?DateTimeImmutable {

		if ($dateValue instanceof DateTimeImmutable) {
			// Already a DateTimeImmutable
			return $dateValue;
		} elseif (is_int($dateValue) && $dateValue > 0) {
			// Treat as Unix timestamp (in seconds) and create DateTimeImmutable
			return (new DateTimeImmutable())->setTimestamp($dateValue);
		} elseif (is_string($dateValue) && !empty($dateValue)) {
			// Treat as ISO 8601 date string and create DateTimeImmutable
			return new DateTimeImmutable($dateValue);
		}
		return null;
	}

	public static function getDateTimeIso(
		null|int|string|DateTimeImmutable $dateValue,
	): ?string {
		$dateTime = self::getDateTimeImmutable($dateValue);
		return !$dateTime ? null : $dateTime->format(DateTime::ATOM);
	}

	/**
	 * Convert duration to normalized DateInterval
	 *
	 * @param null|int|string|DateInterval $duration Duration in seconds
	 * @param null|int|string|DateTimeImmutable $baseDate Offset date for context
	 * @return null|DateInterval Normalized DateInterval object or null if duration is null or invalid
	 */
	public static function getDateInterval(
		null|int|string|DateInterval $duration,
		null|int|string|DateTimeImmutable $baseDate = null,
	): ?DateInterval {

		if (is_string($duration) && !empty($duration)) {
			// If duration is a string, assume it's an ISO 8601 duration and create DateInterval directly
			$duration = new DateInterval($duration);
		} elseif (is_int($duration)) {
			// special handling if duration is a multiple of 86400 seconds, which is a common case for daylong options, to avoid issues with daylight saving time changes
			if ($duration % 86400 === 0) {
				$days = $duration / 86400;
				$duration = new DateInterval('P' . $days . 'D');
			} else {
				// If duration is an integer, treat it as seconds and create a DateInterval with only seconds
				$duration = new DateInterval('PT' . $duration . 'S');
			}
		} elseif (!$duration instanceof DateInterval) {
			return null; // Invalid duration input
		}

		$baseDate = self::getDateTimeImmutable($baseDate);

		if (self::dateIntervalToSeconds($duration, $baseDate) === 0) {
			return null; // Return null for zero duration
		}

		if ($baseDate !== null) {
			$endDate = $baseDate->add($duration);
			$duration = $baseDate->diff($endDate);
		}

		return $duration;
	}

	/** @psalm-suppress PossiblyUnusedMethod
	 * Will be used in migration
	 */
	public static function getIntervalIso(
		null|int|string|DateInterval $duration,
		null|int|string|DateTimeImmutable $baseDate = null,
	): ?string {
		$dateInterval = self::getDateInterval($duration, $baseDate);
		return self::dateIntervalToIso($dateInterval);
	}

	/**
	 * Convert DateInterval to total seconds based on a base date
	 * This is necessary because DateInterval does not have a built-in method to get total seconds, and the actual duration in seconds can vary due to factors like daylight saving time changes when dealing with daylong options.
	 *
	 * @param DateInterval $interval The DateInterval to convert
	 * @param null|DateTimeImmutable $baseDate The base date to use for calculating the end date and total seconds. If null or missing, the current date and time will be used as the base date.
	 * @return int Total seconds represented by the DateInterval based on the base date
	 */
	public static function dateIntervalToSeconds(
		DateInterval $interval,
		?DateTimeImmutable $baseDate = null,
	): int {
		if ($baseDate === null) {
			$baseDate = new DateTimeImmutable();
		}
		$endDate = $baseDate->add($interval);
		return (int)($endDate->getTimestamp() - $baseDate->getTimestamp());
	}

	/**
	 * Check if the start and end date represent a daylong option, which
	 * means the option spans over whole days without specific time,
	 * by checking if the start time is at 00:00 and the duration does
	 * not include any hours, minutes, or seconds.
	 *
	 * @param DateTimeImmutable $startDate The start date of the option
	 * @param DateTimeImmutable $endDate The end date of the option
	 * @return bool True if the option is daylong, false otherwise
	 */
	private static function isDaylong(DateTimeImmutable $startDate, DateTimeImmutable $endDate): bool {
		$dateInterval = $startDate->diff($endDate);

		if (
			$startDate->format('H') === '00'
			&& $dateInterval->h + $dateInterval->i + $dateInterval->h === 0
		) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the end date is on the same day as the start date,
	 * with an optional adjustment to adjust a daylong event to end
	 * at the last second of the day instead of the first second of
	 * the following day.
	 *
	 * @param DateTimeImmutable $dateTime The start date to compare against
	 * @param DateInterval $duration The duration to add to the start date to get the end date
	 * @param string $adjustment Optional ISO 8601 duration string to adjust the end date for comparison, default is 'PT0S' (no adjustment)
	 */
	private static function getSameDay(
		DateTimeImmutable $dateTime,
		DateInterval $duration,
		string $adjustment = 'PT0S',
	): bool {
		$endDate = $dateTime->add($duration);
		if (self::isDaylong($dateTime, $endDate)) {
			// For daylong options, we adjust the end date by subtracting a second to represent the last moment of the day
			$adjustment = 'PT1S';
		}
		$adjustment = new DateInterval($adjustment);
		return $dateTime->format('Y-m-d') === $dateTime->add($duration)->sub($adjustment)->format('Y-m-d');
	}

	/**
	 * Helper method to get a date string for a given DateTimeImmutable and format,
	 * using the provided localization service if available.
	 * If no localization service is provided, it returns an ISO 8601 formatted date string in UTC.
	 *
	 * @param DateTimeImmutable $dateTimeBase Date to format
	 * @param string $format Format key to use for localization (e.g., 'datetime', 'date', 'time')
	 * @param IL10N|null $l10n Optional localization service to use for formatting the date string
	 * @return string Localized date string if IL10N is provided, otherwise an ISO 8601 formatted date string in UTC
	 */
	private static function getDateTimeString(
		DateTimeImmutable $dateTimeBase,
		string $format,
		?IL10N $l10n = null,
	): string {
		if ($l10n) {
			return (string)$l10n->l(
				$format,
				DateTime::createFromImmutable($dateTimeBase)
			);
		} else {
			// always use UTC for ISO 8601 formatted date string
			return $dateTimeBase->setTimezone(new DateTimeZone('UTC'))->format(DateTime::ATOM);
		}
	}

	/**
	 * Get a date string representing the date and time range of an option, formatted according to the provided localization service.
	 *
	 * @param DateTimeImmutable $dateTimeBase Date to format
	 * @param null|DateInterval $duration Duration as DateInterval for formatting a time range
	 * @param IL10N $l10n Localization service to use for formatting the date string
	 * @return string if IL10N is provided, a localized date string; otherwise, an ISO 8601 formatted date string
	 * @throws InsufficientAttributesException if option has no valid timestamp
	 */
	public static function getDateString(
		DateTimeImmutable $dateTimeBase,
		?DateInterval $duration = null,
		?IL10N $l10n = null,
	): string {

		$dateTimeString = self::getDateTimeString($dateTimeBase, 'datetime', $l10n);

		// get normalized duration for consistent handling of different duration formats and zero duration
		$duration = self::getDateInterval($duration, $dateTimeBase);

		// If there is no duration, just return the UTC datetime string from the base date
		if (!$duration) {
			return $dateTimeString;
		}

		// If no localization service is provided, return UTC ISO 8601 formatted date range
		if (!$l10n) {
			return
				self::getDateTimeString($dateTimeBase, 'datetime')
				. ' - '
				. self::getDateTimeString($dateTimeBase->add($duration), 'datetime');
		}

		// If the option spans over one or more whole days, the option represents only the days without time
		// which is calculated by adding the duration
		if (self::isDaylong($dateTimeBase, $dateTimeBase->add($duration))) {
			$dateTimeString = self::getDateTimeString($dateTimeBase, 'date', $l10n);

			// if the end day is the same as the start day, we return just one date without a range
			// this means we have a one day span
			if (self::getSameDay($dateTimeBase, $duration, 'PT1S')) {
				return $dateTimeString;
			}
			// adjust the end by substracting a second, to represent the last moment at the day and not the first moment of the following day
			return $dateTimeString . ' - ' . self::getDateTimeString($dateTimeBase->add($duration)->sub(new DateInterval('PT1S')), 'date', $l10n);
		}

		// From here on we have a time range that does not span over whole days, so we include the time in the end date as well
		if (self::getSameDay($dateTimeBase, $duration)) {
			// If the end date is the same day as the start date, we can omit the date in the end date and only show the time
			return $dateTimeString . ' - ' . self::getDateTimeString($dateTimeBase->add($duration), 'time', $l10n);
		}

		// If the end date is a different day than the start date, we need to show the date and time in the end date as well
		return $dateTimeString . ' - ' . self::getDateTimeString($dateTimeBase->add($duration), 'datetime', $l10n);

	}

	/**
	 * Get ISO 8601 duration string from DateInterval
	 *
	 * Possible alternative implementation found at: https://stackoverflow.com/questions/33787039/format-dateinterval-as-iso8601/42598056#42598056
	 *
	 * @param null|DateInterval $interval DateInterval to convert
	 * @param compressed boolean Whether to return a compressed ISO 8601 duration string with only non-zero parts (true) or the standard format with all parts (false, default)
	 * @return null|string Compressed ISO 8601 duration string, null if the interval is null or represents a zero duration
	 */
	public static function dateIntervalToIso(?DateInterval $interval, bool $compressed = false): ?string {
		// No duration returns null
		if ($interval === null || self::dateIntervalToSeconds($interval) === 0) {
			return null;
		}

		if (!$compressed) {
			return $interval->format('P%yY%mM%dDT%hH%iM%sS');
		}

		// TODO: Consider omitting the following code
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
			return null; // For zero duration
		}

		return $result;
	}
}
