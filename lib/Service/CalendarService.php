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
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Preferences;
use OCA\Polls\Model\CalendarEvent;
use OCA\Polls\Model\User\CurrentUser;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager as CalendarManager;

class CalendarService {
	/** @var CurrentUser */
	private $currentUser;

	/** @var CalendarManager */
	private $calendarManager;

	/** @var ICalendar[] */
	private $calendars;

	/** @var PreferencesService */
	private $preferencesService;

	/** @var Preferences */
	private $preferences;

	/** @var OptionMapper */
	private $optionMapper;

	public function __construct(
		CalendarManager $calendarManager,
		PreferencesService $preferencesService,
		OptionMapper $optionMapper,
		CurrentUser $currentUser
	) {
		$this->currentUser = $currentUser;
		$this->calendarManager = $calendarManager;
		$this->preferencesService = $preferencesService;
		$this->optionMapper = $optionMapper;
		$this->preferences = $this->preferencesService->get();
		$this->getCalendarsForPrincipal();
	}

	/**
	 * getCalendars -
	 */
	private function getCalendarsForPrincipal(string $userId = ''): void {
		if ($userId) {
			$principalUri = 'principals/users/' . $userId;
		} else {
			$principalUri = $this->currentUser->getPrincipalUri();
		}

		$this->calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri);
	}


	/**
	 * getTimerange - set timeranges to search within based on the option's time information
	 *
	 * @return DateTimeImmutable[]
	 *
	 * @psalm-return array{from: DateTimeImmutable, to: DateTimeImmutable}
	 */
	private function getTimerange(int $optionId, DateTimeZone $timezone): array {
		$option = $this->optionMapper->find($optionId);
		$searchIntervalBefore = new DateInterval('PT' . $this->preferences->getCheckCalendarsBefore() . 'H');
		$searchIntervalAfter = new DateInterval('PT' . $this->preferences->getCheckCalendarsAfter() . 'H');

		$from = (new DateTime())
			->setTimeZone($timezone)
			->setTimestamp($option->getTimestamp())
			->sub($searchIntervalBefore);
		$to = (new DateTime())
			->setTimeZone($timezone)
			->setTimestamp($option->getTimestamp() + $option->getDuration())
			->add($searchIntervalAfter);

		return [
			'from' => DateTimeImmutable::createFromMutable($from),
			'to' => DateTimeImmutable::createFromMutable($to),
		];
	}

	private function searchEventsByTimeRange(DateTimeImmutable $from, DateTimeImmutable $to): ?array {
		$query = $this->calendarManager->newQuery($this->currentUser->getPrincipalUri());
		$query->setTimerangeStart($from);
		$query->setTimerangeEnd($to);

		foreach ($this->calendars as $calendar) {
			if (in_array($calendar->getKey(), json_decode($this->preferences->getPreferences())->checkCalendars)) {
				$query->addSearchCalendar($calendar->getUri());
			}
		}
		return $this->calendarManager->searchForPrincipal($query);
	}

	/**
	 * getEvents - get events from the user's calendars inside given timespan
	 *
	 * @return CalendarEvent[]
	 *
	 * @psalm-return list<CalendarEvent>
	 */
	public function getEvents(int $optionId, string $tz): array {
		$timezone = new DateTimeZone($tz);
		$timerange = $this->getTimerange($optionId, $timezone);

		$events = [];
		$foundEvents = $this->searchEventsByTimeRange($timerange['from'], $timerange['to']);

		foreach ($foundEvents as $event) {
			$calendar = $this->getCalendarFromEvent($event);
			if ($calendar === null) {
				continue;
			}

			$calendarEvent = new CalendarEvent($event, $calendar, $timerange['from'], $timerange['to'], $timezone);
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
	 * Get user's calendars
	 *
	 * @return array[]
	 *
	 * @psalm-return list<array{name: mixed, key: mixed, displayColor: mixed, permissions: mixed}>
	 */
	public function getCalendars(): array {
		$calendars = [];
		foreach ($this->calendars as $calendar) {
			$calendars[] = [
				'key' => $calendar->getKey(),
				'calendarUri' => $calendar->getUri(), // since NC23
				'name' => $calendar->getDisplayName(),
				'displayColor' => $calendar->getDisplayColor(),
				'permissions' => $calendar->getPermissions(),
				'calendar' => $calendar,
			];
		}
		return $calendars;
	}
}
