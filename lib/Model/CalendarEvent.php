<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use OCP\Calendar\ICalendar;
use RRule\RRule;

class CalendarEvent implements \JsonSerializable {
	public const TYPE_DATE = 'date';
	public const TYPE_DATE_TIME = 'dateTime';
	public const CALENDAR_PREFIX_URI = 'calendar/';
	private const MAX_OCURRENCIES = 100;

	protected array $occurrences = [];
	protected bool $hasRRule;
	protected array $rRule;
	protected ?int $matchOccurrence = null;
	protected array $event;

	public function __construct(
		protected array $iCal,
		protected ICalendar $calendar,
		protected ?DateTimeImmutable $filterFrom = null,
		protected ?DateTimeImmutable $filterTo = null,
		protected ?DateTimeZone $timezone = null,
	) {
		$this->event = $this->iCal['objects'][0];
		$this->hasRRule = isset($this->event['RRULE']);
		$this->rRule = [];
		$this->fixAllDay();
		$this->buildRRule();
		$this->calculateOccurrences();
	}

	// Getters for calendar information
	public function getCalendarName(): ?string {
		return $this->calendar->getDisplayName();
	}

	public function setOccurrence(int $index): void {
		if ($this->occurrences) {
			$this->matchOccurrence = $index;
		}
	}

	public function getCalendarKey(): string {
		return $this->calendar->getKey();
	}

	public function getCalendarUri(): string {
		try {
			return self::CALENDAR_PREFIX_URI . $this->calendar->getUri();
		} catch (\Exception $e) {
			return '';
		}
	}

	public function getDisplayColor(): ?string {
		return $this->calendar->getDisplayColor();
	}

	// Getters for common event description
	public function getId(): string {
		return (string)$this->iCal['id'];
	}

	public function getUID(): string {
		return $this->event['UID'][0];
	}

	public function getSummary(): string {
		return $this->event['SUMMARY'][0];
	}

	public function getDescription(): string {
		return $this->event['DESCRIPTION'][0] ?? '';
	}

	public function getLocation(): string {
		return $this->event['LOCATION'][0] ?? '';
	}

	// Getters for the event's scheduling information
	public function getAllDay(): string {
		return $this->getType() === self::TYPE_DATE ? $this->event['DTSTART'][0]->format('Y-m-d') : '';
	}

	public function getType(): string {
		// TODO: Properly Sabre Object handling
		if (isset($this->event['DTSTART'][1]['VALUE'])) {
			if ($this->event['DTSTART'][1]['VALUE'] === 'DATE') {
				return self::TYPE_DATE;
			}
		}
		return self::TYPE_DATE_TIME;
	}

	/**
	 * Get the event start from the base event
	 */
	public function getBaseStart(): DateTimeImmutable {
		return $this->event['DTSTART'][0];
	}

	/**
	 * Get the event end from the base event
	 * If not set return the start of the base event
	 */
	public function getBaseEnd(): DateTimeImmutable {
		return $this->event['DTEND'][0] ?? $this->event['DTSTART'][0];
	}

	/**
	 * Get the event start for the matched occurence
	 */
	public function getStart(): DateTimeImmutable {
		if ($this->occurrences != null && $this->matchOccurrence !== null) {
			return DateTimeImmutable::createFromMutable($this->occurrences[$this->matchOccurrence]);
		}
		return $this->getBaseStart();
	}

	/**
	 * Calculate the end of the matched occurence by adding the diff of the base start/end
	 */
	public function getEnd(): DateTimeImmutable {
		return $this->getStart()->add($this->getDiff());
	}

	/**
	 * Calculate the event duration as DateInterval
	 */
	public function getDiff(): DateInterval {
		return $this->getBaseStart()->diff($this->getBaseEnd());
	}

	/**
	 * Calculate the event duration in seconds
	 */
	public function getDuration(): int {
		return $this->getBaseEnd()->getTimestamp() - $this->getBaseStart()->getTimestamp();
	}

	public function getStatus(): string {
		// TODO: Understand if the status is the status of the base event or occurrency
		// Currently the status is taken from the base event.
		return $this->event['STATUS'][0] ?? '';
	}

	// Getters and functions for recurrence handling
	public function getHasRRule(): bool {
		return $this->hasRRule;
	}

	public function getOccurrences() : array {
		return $this->occurrences;
	}

	private function fixAllDay(): void {
		// force all day events to 00:00 in the user's timezone
		if ($this->getType() === self::TYPE_DATE) {
			$this->event['DTSTART'][0] = $this->event['DTSTART'][0]->setTimezone($this->timezone);
			$this->event['DTEND'][0] = $this->event['DTEND'][0]->setTimezone($this->timezone);
			$this->event['DTSTART'][0] = $this->event['DTSTART'][0]->setTime(0, 0);
			$this->event['DTEND'][0] = $this->event['DTEND'][0]->setTime(0, 0);
		}
	}

	private function buildRRule(): void {
		if (!$this->getHasRRule()) {
			return;
		}

		preg_match_all('/([^;= ]+)=([^;= ]+)/', $this->event['RRULE'][0], $r);
		$this->rRule = array_combine($r[1], $r[2]);

		$this->rRule['DTSTART'] = $this->getBaseStart();

		// force limiting occurrences to the filter boundary, if set
		if ($this->filterTo) {
			$this->rRule['UNTIL'] = $this->filterTo;
		}
	}

	private function calculateOccurrences(): void {
		if (!$this->getHasRRule()) {
			return;
		}
		$rRule = new RRule($this->rRule);

		$this->occurrences = [];

		foreach ($rRule as $occurrence) {
			if ($this->filterFrom
			  && (($occurrence->getTimestamp() + $this->getDuration()) < $this->filterFrom->getTimestamp())) {
				// skip occurrences before filter span
				continue;
			}

			if ($this->filterTo && $occurrence->getTimestamp() > $this->filterTo->getTimestamp()) {
				// skip occurrences after filter span
				return;
			}

			$this->occurrences[] = $occurrence;
			// prevent endles loop, if no until is set
			if (count($this->occurrences) > self::MAX_OCURRENCIES - 1) {
				return;
			}
		}
	}

	/** @psalm-suppress PossiblyUnusedMethod */
	public function jsonSerialize(): array {
		return	[
			'id' => $this->getId(),
			'UID' => $this->getUID(),
			'calendarKey' => $this->getCalendarKey(),
			'calendarUri' => $this->getCalendarUri(),
			'calendarName' => $this->getCalendarName(),
			'displayColor' => $this->getDisplayColor(),
			'allDay' => $this->getAllDay(),
			'description' => $this->getDescription(),
			'start' => $this->getStart()->getTimestamp(),
			'location' => $this->getLocation(),
			'end' => $this->getEnd()->getTimestamp(),
			'status' => $this->getStatus(),
			'summary' => $this->getSummary(),
			'type' => $this->getType(),
		];
	}
}
