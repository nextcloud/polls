<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

/**
 * A wrapper around PHP's built-in DateInterval class that provides additional functionality
 * for handling durations in a more flexible way, including support for ISO 8601 duration strings,
 * integer durations in seconds, and determining if an interval represents a full day or more.
 */
class DateTimeImmutable extends \DateTimeImmutable {
	public const DEFAULT_TIMEZONE = 'UTC';
	public const DEFAULT_DATE_TIME_FORMAT = \DateTime::ATOM;

	public function __construct(
		null|int|string|DateTimeImmutable $dateValue = null,
	) {
		if ($dateValue instanceof DateTimeImmutable) {
			// Already a DateTimeImmutable
			parent::__construct($dateValue->format(self::DEFAULT_DATE_TIME_FORMAT));
		} elseif (is_int($dateValue) && $dateValue > 0) {
			// Treat as Unix timestamp (in seconds) and create DateTimeImmutable
			parent::__construct('@' . strval($dateValue));
		} elseif (is_string($dateValue) && !empty($dateValue)) {
			// Treat as ISO 8601 date string and create DateTimeImmutable
			parent::__construct($dateValue);
		} else {
			// Default to Unix epoch if input is null, empty string, or invalid
			parent::__construct('@0');
		}
	}

	public function isStartOfDay(): bool {
		return $this->format('H:i:s') === '00:00:00';
	}

	/**
	 * Get the ISO 8601 timestamp string representation of this DateTimeImmutable.
	 * If the date is the Unix epoch (1970-01-01T00:00:00+00:00), this method returns null,
	 *
	 * @return string|null The ISO 8601 duration string or null if the interval is zero.
	 */
	public function getISO(): ?string {
		if ($this->getTimestamp() === 0) {
			return null;
		}
		return $this->format(self::DEFAULT_DATE_TIME_FORMAT);
	}

	public function getISODate(): ?string {
		if ($this->getTimestamp() === 0) {
			return null;
		}
		return $this->format('Y-m-d');
	}
}
