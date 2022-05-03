<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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


namespace OCA\Polls\Service;

use DateTime;
use DateTimeImmutable;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager as CalendarManager;
use OCP\Util;
use OCA\Polls\Model\CalendarEvent;
use OCA\Polls\Db\Preferences;
use OCA\Polls\Model\UserGroup\CurrentUser;

class CalendarService {
	/** @var CurrentUser */
	private $currentUser ;

	/** @var CalendarManager */
	private $calendarManager;

	/** @var ICalendar[] */
	private $calendars;

	/** @var array */
	private $calendarMapKeys;

	/** @var PreferencesService */
	private $preferencesService;

	/** @var Preferences */
	private $preferences;

	public function __construct(
		CalendarManager $calendarManager,
		PreferencesService $preferencesService,
		CurrentUser $currentUser
	) {
		$this->currentUser = $currentUser;
		$this->calendarManager = $calendarManager;
		$this->preferencesService = $preferencesService;
		$this->preferences = $this->preferencesService->get();
		$this->getCalendarsForPrincipal();
	}

	/**
	 * getCalendars -
	 *
	 * @return ICalendar[]
	 *
	 * @psalm-return list<ICalendar>
	 */
	public function getCalendarsForPrincipal(string $userId = ''): array {
		if (Util::getVersion()[0] < 24) {
			// deprecated since NC23
			$this->calendars = $this->calendarManager->getCalendars();
			return $this->calendars;
		}

		// use from NC24 on
		if ($userId) {
			$principalUri = 'principals/users/' . $userId;
		} else {
			$principalUri = $this->currentUser->getPrincipalUri();
		}

		$this->calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri);
		// $this->calendars[] = 'ncyagstde-2';
		return $this->calendars;
	}

	/**
	 * getCalendars -
	 *
	 * @return ICalendar[]
	 *
	 * @psalm-return list<ICalendar>
	 */
	public function getCalendarsForPrincipal(string $userId = ''): array {
		if (Util::getVersion()[0] < 24) {
			// deprecated since NC23
			$this->calendars = $this->calendarManager->getCalendars();
			return $this->calendars;
		}

		// use from NC24 on
		if ($userId) {
			$principalUri = 'principals/users/' . $userId;
		} else {
			$principalUri = $this->currentUser->getPrincipalUri();
		}

		$this->calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri);
		// $this->calendars[] = 'ncyagstde-2';
		return $this->calendars;
	}

	/**
	 * getEvents - get events from the user's calendars inside given timespan
	 *
	 * @return CalendarEvent[]
	 *
	 * @psalm-return list<CalendarEvent>
	 */
	public function getEvents(DateTime $from, DateTime $to): array {
		$from = DateTimeImmutable::createFromMutable($from);
		$to = DateTimeImmutable::createFromMutable($to);

		if (Util::getVersion()[0] < 24) {
			// deprecated since NC24
			\OC::$server->getLogger()->debug('calling legacy version');
			return $this->getEventsLegcy($from, $to);
		}

		// use from NC24 on
		$events = [];
		$query = $this->calendarManager->newQuery($this->currentUser->getPrincipalUri());
		$query->setTimerangeStart($from);
		$query->setTimerangeEnd($to);

		foreach ($this->calendars as $calendar) {
			if (in_array($calendar->getKey(), json_decode($this->preferences->getPreferences())->checkCalendars)) {
				$query->addSearchCalendar($calendar->getUri());
			}
		}

		$foundEvents = $this->calendarManager->searchForPrincipal($query);

		foreach ($foundEvents as $event) {
			$calendar = $this->getCalendarFromEvent($event);
			if ($calendar === null) {
				continue;
			}

			$calendarEvent = new CalendarEvent($event, $calendar, $from, $to);
			if ($calendarEvent->getOccurrences()) {
				for ($index = 0; $index < count($calendarEvent->getOccurrences()); $index++) {
					$calendarEvent->setOccurrence($index);
					array_push($events, $calendarEvent);
				}
			} else {
				array_push($events, $calendarEvent);
			}
		}
		return $events;
	}

	private function getCalendarFromEvent(array $event): ?ICalendar {
		foreach ($this->calendars as $calendar) {
			if ($calendar->getKey() === $event['calendar-key']) {
				return $calendar;
			}
		}
		return null;
	}
	/**
	 * getEventsLegacy - get events from the user's calendars inside given timespan
	 *
	 * @return CalendarEvent[]
	 *
	 * @deprecated since NC23
	 *
	 * @psalm-return list<CalendarEvent>
	 */
	private function getEventsLegcy(DateTimeImmutable $from, DateTimeImmutable $to): array {
		$events = [];
		$query = $this->calendarManager->newQuery($this->currentUser->getPrincipalUri());
		$query->setTimerangeStart($from);
		$query->setTimerangeEnd($to);

		foreach ($this->calendars as $calendar) {
			if (in_array($calendar->getKey(), json_decode($this->preferences->getPreferences())->checkCalendars)) {
				$query->addSearchCalendar($calendar->getUri());
			}
		}

		$foundEvents = $this->calendarManager->searchForPrincipal($query);

		foreach ($foundEvents as $event) {
			$calendar = $this->getCalendarFromEvent($event);
			if ($calendar === null) {
				continue;
			}

			$calendarEvent = new CalendarEvent($event, $calendar, $from, $to);
			if ($calendarEvent->getOccurrences()) {
				for ($index = 0; $index < count($calendarEvent->getOccurrences()); $index++) {
					$calendarEvent->setOccurrence($index);
					array_push($events, $calendarEvent);
				}
			} else {
				array_push($events, $calendarEvent);
			}
		}
		return $events;
	}

	private function getCalendarFromEvent(array $event): ?ICalendar {
		foreach ($this->calendars as $calendar) {
			if ($calendar->getKey() === $event['calendar-key']) {
				return $calendar;
			}
		}
		return null;
	}
	/**
	 * getEventsLegacy - get events from the user's calendars inside given timespan
	 *
	 * @return CalendarEvent[]
	 *
	 * @deprecated since NC23
	 *
	 * @psalm-return list<CalendarEvent>
	 */
	private function getEventsLegcy(DateTimeImmutable $from, DateTimeImmutable $to): array {
		$events = [];
		foreach ($this->calendars as $calendar) {

			// Skip not configured calendars
			if (!in_array($calendar->getKey(), json_decode($this->preferences->getPreferences())->checkCalendars)) {
				continue;
			}

			// search for all events which
			// - start before the end of the requested timespan ($to) and
			// - end after the start of the requested timespan ($from)
			$foundEvents = $calendar->search('', ['SUMMARY'], ['timerange' => ['start' => $from, 'end' => $to]]);
			// \OC::$server->getLogger()->error('foundEvents: ' . json_encode($foundEvents));
			foreach ($foundEvents as $event) {
				$calendarEvent = new CalendarEvent($event, $calendar, $from, $to);
				// since we get back recurring events of other days, just make sure this event
				// matches the search pattern
				// TODO: identify possible time zone issues, when handling all day events
				if (($from->getTimestamp() < $calendarEvent->getEnd())
					&& ($to->getTimestamp() > $calendarEvent->getStart())) {
				}
				array_push($events, $calendarEvent);
				// array_push($events, $calendarEvent);
			}
		}
		return $events;
	}

	/**
	 * Get user's calendars
	 *
	 * @return array[]
	 *
	 * @psalm-return list<array{name: mixed, key: mixed, displayColor: mixed, permissions: mixed}>
	 */
	public function getCalendars(): array {
		$calendars = [];
		foreach ($this->calendars as $calendar) {
			if (Util::getVersion()[0] < 24) {
				$calendars[] = [
					'key' => $calendar->getKey(),
					'calendarUri' => '', // since NC23
					'name' => $calendar->getDisplayName(),
					'displayColor' => $calendar->getDisplayColor(),
					'permissions' => $calendar->getPermissions(),
				];
			} else {
				$calendars[] = [
					'key' => $calendar->getKey(),
					'calendarUri' => $calendar->getUri(), // since NC23
					'name' => $calendar->getDisplayName(),
					'displayColor' => $calendar->getDisplayColor(),
					'permissions' => $calendar->getPermissions(),
					'calendar' => $calendar,
				];
			}
		}
		return $calendars;
	}
}
