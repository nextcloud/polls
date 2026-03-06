<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

use DateTime;
use OCP\IL10N;

class DateTimeIntervalText {
	public const FORMAT_DATE_TIME = 'datetime';
	public const FORMAT_DATE = 'date';
	public const FORMAT_TIME = 'time';

	protected DateTimeImmutable $start;
	protected DateInterval $interval;

	public function __construct(
		protected IL10N $l10n,
	) {
		$this->start = new DateTimeImmutable();
		$this->interval = new DateInterval('PT0S');
	}

	public function setStartDateTime(DateTimeImmutable $start): self {
		$this->start = $start;
		return $this;
	}

	public function setInterval(DateInterval $interval): self {
		$this->interval = $interval;
		return $this;
	}

	public function getDateTime(): DateTimeImmutable {
		return $this->start;
	}

	public function getInterval(): DateInterval {
		return $this->interval;
	}

	public function getEndDateTime(): DateTimeImmutable {
		return $this->start->add($this->interval);
	}

	public function getLocalizedDateTimeString(): string {
		$dateTimeStart = $this->getDateTime();

		if ($this->getInterval()->isZeroDuration()) {
			return $this->getDateTimeString($dateTimeStart, $dateTimeStart->isStartOfDay() ? self::FORMAT_DATE : self::FORMAT_DATE_TIME);
		}

		$dateTimeEnd = $this->getEndDateTime();

		// Daylong events (start at 00:00:00 and lasts one or more days)
		if ($this->getInterval()->isDayInterval()
			&& $dateTimeStart->isStartOfDay()
		) {

			// Adjust the end date by minus one day
			$dateTimeEnd = $dateTimeEnd->sub(new DateInterval('P1D'));

			if (
				// start and end are on the same day (Interval is set to one day)
				$dateTimeEnd->getISODate() === $dateTimeStart->getISODate()) {
				// start and end are on the same day
				return $this->getDateTimeString($dateTimeStart, self::FORMAT_DATE);
			}

			return $this->getDateTimeString($dateTimeStart, self::FORMAT_DATE)
			. ' – '
			. $this->getDateTimeString($dateTimeEnd, self::FORMAT_DATE);
		}

		// From here on we have a time range that does not span over whole days
		// use initial end date and add the time information

		if ($dateTimeEnd->getISODate() === $dateTimeStart->getISODate()) {
			// If the end date is the same day as the start date, we can omit the date in the end date and only show the time
			return $this->getDateTimeString($dateTimeStart)
				. ' – '
				. $this->getDateTimeString($dateTimeEnd, self::FORMAT_TIME);
		}

		return $this->getDateTimeString($dateTimeStart)
			. ' – '
			. $this->getDateTimeString($dateTimeEnd);
	}

	/**
	 * Helper method to get a date string for a given DateTimeImmutable and format,
	 * using the provided localization service if available.
	 * If no localization service is provided, it returns an ISO 8601 formatted date string in UTC.
	 *
	 * @param DateTimeImmutable $dateTime The date and time to format
	 * @param string $format Format key to use for localization (e.g., 'datetime', 'date', 'time')
	 * @return string Localized date string
	 */
	private function getDateTimeString(
		DateTimeImmutable $dateTime,
		string $format = self::FORMAT_DATE_TIME,
	): string {
		return (string)$this->l10n->l(
			$format,
			DateTime::createFromImmutable($dateTime)
		);
	}

}
