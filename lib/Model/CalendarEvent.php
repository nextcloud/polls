<?php
/**
 * @copyright Copyright (c) 2020 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Model;

use DateTimeImmutable;
use DateInterval;
use DateTimeZone;
use \OCP\Calendar\ICalendar;
use RRule\RRule;

class CalendarEvent implements \JsonSerializable {
	public const TYPE_DATE = 'date';
	public const TYPE_DATE_TIME = 'dateTime';
	public const CALENDAR_PREFIX_URI = 'calendar/';
	private const MAX_OCURRENCIES = 100;

	/** @var array */
	protected $iCal;

	/** @var array */
	protected $occurrences = null;
	
	/** @var bool */
	protected $hasRRule;

	/** @var array */
	protected $rRule = null;

	/** @var int */
	protected $matchOccurrence = null;

	/** @var array */
	protected $event;

	/** @var ICalendar */
	protected $calendar;

	/** @var DateTimeImmutable */
	protected $filterFrom;
	
	/** @var DateTimeImmutable */
	protected $filterTo;
	
	/** @var DateTimeZone */
	protected $timezone;

	public function __construct(
		array $iCal,
		ICalendar $calendar,
		DateTimeImmutable $filterFrom = null,
		DateTimeImmutable $filterTo = null,
		DateTimeZone $timezone = null
	) {
		$this->iCal = $iCal;
		$this->calendar = $calendar;
		$this->filterFrom = $filterFrom;
		$this->filterTo = $filterTo;
		$this->timezone = $timezone;
		$this->event = $this->iCal['objects'][0];
		$this->hasRRule = isset($this->event['RRULE']);
		$this->fixAllDay();
		$this->buildRRule();
		$this->calculateOccurrences();
	}

	// Getters for calendar information
	public function getCalendarName(): ?string {
		return $this->calendar->getDisplayName();
	}

	public function setOccurrence(int $index) {
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
		return $this->iCal['id'];
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
		if (isset($this->event['DTSTART'][1]['VALUE'])) {
			if (strtoupper($this->event['DTSTART'][1]['VALUE']) === 'DATE') {
				return self::TYPE_DATE;
			}
		}
		return self::TYPE_DATE_TIME;
	}

	public function getBaseStart() : ?DateTimeImmutable {
		if (isset($this->event['DTSTART'][0])) {
			return $this->event['DTSTART'][0];
			// return (new DateTimeImmutable())->setTimestamp($this->event['DTSTART'][0]->getTimestamp());
		}
		return null;
	}

	public function getBaseEnd() : ?DateTimeImmutable {
		if (isset($this->event['DTEND'][0])) {
			return $this->event['DTEND'][0];
		}
		return null;
	}

	public function getStart() : ?DateTimeImmutable {
		if ($this->occurrences != null && $this->matchOccurrence !== null) {
			return DateTimeImmutable::createFromMutable($this->occurrences[$this->matchOccurrence]);
		}
		return $this->getBaseStart();
	}

	public function getEnd(): ?DateTimeImmutable {
		if ($this->getBaseEnd() !== null) {
			return $this->getStart()->add($this->getDiff());
		}
		return null;
	}

	public function getDiff() : ?DateInterval {
		if ($this->getBaseStart() && $this->getBaseEnd()) {
			return $this->getBaseStart()->diff($this->getBaseEnd());
			// return $this->getBaseEnd()->getTimestamp() - $this->getBaseStart()->getTimestamp();
		}
		return null;
	}

	public function getDuration() : int {
		if ($this->getBaseStart() && $this->getBaseEnd()) {
			return $this->getBaseEnd()->getTimestamp() - $this->getBaseStart()->getTimestamp();
		}
		return 0;
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

	public function getRRule(): ?array {
		return $this->rRule;
	}

	public function getOccurrences() : ?array {
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

	private function buildRRule() : void {
		if (!$this->getHasRRule()) {
			return;
		}
		
		preg_match_all("/([^;= ]+)=([^;= ]+)/", $this->event['RRULE'][0], $r);
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

	public function getICal(): array {
		return $this->iCal;
	}

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
			// 'duration' => $this->getDuration(),
			// 'iCal' => $this->getICal(),
			// 'hasRRule' => $this->getHasRRule(),
			// 'rRule' => $this->getRRule(),
			// 'timezone' => $this->getBaseStart()->getTimeZone(),
			// 'occurrences' => $this->getOccurrences(),
			// 'times' => [
			// 	'start' => $this->getStart(),
			// 	'end' => $this->getEnd(),
			// ],
		];
	}
}
