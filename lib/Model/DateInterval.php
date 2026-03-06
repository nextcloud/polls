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
class DateInterval extends \DateInterval {
	public function __construct(
		null|int|string|DateInterval $duration,
	) {
		if (is_string($duration) && !empty($duration)) {
			parent::__construct($duration);
		} elseif (is_int($duration) && $duration !== 0 && $duration % 86400 === 0) {
			// special handling if duration is a multiple of 86400 seconds,
			// which is a common case for daylong options, to avoid issues with
			// daylight saving time changes
			parent::__construct('P' . strval($duration / 86400) . 'D');
		} elseif (is_int($duration)) {
			// If duration is an integer, treat it as seconds and create
			// a DateInterval with only seconds
			parent::__construct('PT' . strval($duration) . 'S');
		} elseif ($duration instanceof DateInterval) {
			parent::__construct($duration->format('P%yY%mM%dDT%hH%iM%sS'));
		} else {
			parent::__construct('PT0S');
		}
	}

	public function isZeroDuration(): bool {
		return $this->getSeconds() === 0;
	}

	/**
	 * Get the ISO 8601 duration string representation of this DateInterval.
	 * If the interval is zero (all components are zero), this method returns null.
	 *
	 * @return string|null The ISO 8601 duration string or null if the interval is zero.
	 */
	public function getISO(): ?string {
		if (
			$this->y === 0
			&& $this->m === 0
			&& $this->d === 0
			&& $this->h === 0
			&& $this->i === 0
			&& $this->s === 0
		) {
			return null;
		}
		return $this->format('P%yY%mM%dDT%hH%iM%sS');
	}

	/**
	 * Determine if this DateInterval represents one or more full days.
	 *
	 * @return bool True if the interval represents a full day or more, false otherwise.
	 */
	public function isDayInterval(): bool {
		return
			($this->y + $this->m + $this->d) !== 0
			&& $this->h === 0
			&& $this->i === 0
			&& $this->s === 0;
	}

	/**
	 * Calculate the total number of seconds represented by this DateInterval,
	 * using a base date to account for months and years which can vary in length.
	 * If no base date is provided, the current date and time will be used as the base.
	 *
	 * @param DateTimeImmutable $baseDate The date from which to calculate the interval in seconds.
	 * @return int The total number of seconds represented by this DateInterval.
	 */
	public function getSeconds(DateTimeImmutable $baseDate = new DateTimeImmutable()): int {
		return ($baseDate->add($this)->getTimestamp() - $baseDate->getTimestamp());
	}
}
