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
use OCP\Calendar\IManager as CalendarManager;
use OCA\Polls\Model\CalendarEvent;

class CalendarService {
	private $calendarManager;
	private $calendars;
	private $preferencesService;
	private $preferences;

	public function __construct(
		CalendarManager $calendarManager,
		PreferencesService $preferencesService
	) {
		$this->calendarManager = $calendarManager;
		$this->preferencesService = $preferencesService;
		$this->preferences = $this->preferencesService->get();
		$this->calendars = $this->calendarManager->getCalendars();
	}

	/**
	 * 	 * 	 * getEvents - get events from the user's calendars inside given timespan
	 * 	 *
	 *
	 * @param self $from
	 * @param self $to
	 *
	 * @return CalendarEvent[]
	 *
	 * @psalm-return list<CalendarEvent>
	 */
	public function getEvents(self $from, self $to): array {
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
			foreach ($foundEvents as $event) {
				$calendarEvent = new CalendarEvent($event, $calendar);

				// since we get back recurring events of other days, just make sure this event
				// matches the search pattern
				// TODO: identify possible time zone issues, whan handling all day events
				if (($from->getTimestamp() < $calendarEvent->getEnd())
					&& ($to->getTimestamp() > $calendarEvent->getStart())) {
					array_push($events, $calendarEvent);
				}
			}
		}
		return $events;
	}

	/**
	 * 	 * Get user's calendars
	 *
	 * @return array[]
	 *
	 * @psalm-return list<array{name: mixed, key: mixed, displayColor: mixed, permissions: mixed}>
	 */
	public function getCalendars(): array {
		$calendars =  [];
		foreach ($this->calendars as $calendar) {
			$calendars[] = [
				'name' => $calendar->getDisplayName(),
				'key' => $calendar->getKey(),
				'displayColor' => $calendar->getDisplayColor(),
				'permissions' => $calendar->getPermissions(),
			];
		}
		return $calendars;
	}
}
