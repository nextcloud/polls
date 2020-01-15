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
use OCP\Calendar\ICalendar;

class CalendarService {

	private $calendarManager;
	private $calendars;

	public function __construct(
		CalendarManager $calendarManager
	) {
		$this->calendarManager = $calendarManager;
		$this->calendars = $this->calendarManager->getCalendars();
	}

	/**
	 * Get a list of NC users, groups and contacts
	 * @NoAdminRequired
	 * @param DateTime $from
	 * @param DateTime $to
	 * @return Array
	 */
	public function getEvents($from, $to) {
		$events = [];

		foreach ($this->calendars as $calendar) {
			$foundEvents = $calendar->search('' ,['SUMMARY'], ['timerange' => ['start' => $from, 'end' => $to]]);
			foreach ($foundEvents as $event) {
				array_push($events, [
					'relatedFrom' => $from->getTimestamp(),
					'relatedTo' => $to->getTimestamp(),
					'name' => $calendar->getDisplayName(),
					'key' => $calendar->getKey(),
					'displayColor' => $calendar->getDisplayColor(),
					'permissions' => $calendar->getPermissions(),
					'eventId' => $event['id'],
					'UID' => $event['objects'][0]['UID'][0],
					'summary' => isset($event['objects'][0]['SUMMARY'][0])? $event['objects'][0]['SUMMARY'][0] : '',
					'description' => isset($event['objects'][0]['DESCRIPTION'][0])? $event['objects'][0]['DESCRIPTION'][0] : '',
					'location' => isset($event['objects'][0]['LOCATION'][0]) ? $event['objects'][0]['LOCATION'][0] : '',
					'eventFrom' => isset($event['objects'][0]['DTSTART'][0]) ? $event['objects'][0]['DTSTART'][0]->getTimestamp() : 0,
					'eventTo' => isset($event['objects'][0]['DTEND'][0] ) ? $event['objects'][0]['DTEND'][0]->getTimestamp() : 0,
					'calDav' => $event
				]);
			}
		}
		return $events;
	}

	/**
	 * Get a list of NC users, groups and contacts
	 * @NoAdminRequired
	 * @return Array
	 */
	public function getCalendars() {
		return $this->calendars;
	}


	/**
	 * Get a list of NC users, groups and contacts
	 * @NoAdminRequired
	 * @return Array
	 */
	public function getEventsAround($from) {
		$from = new DateTime($from);
		$to = new DateTime($from);
		$this->$logger->info('from: ' . json_encode($from->sub(new DateInterval('P2H'))));
		$this->$logger->info('to: ' . json_encode($to->sub(new DateInterval('P2H'))));
		return $this->getEvents(
			$from->sub(new DateInterval('P2H')),
			$to->add(new DateInterval('P3H'))
		);
	}

	/**
	 * Get a list of NC users, groups and contacts
	 * @NoAdminRequired
	 * @return Array
	 */
	public function getCalendarsTest($from, $to) {
		return $this->getEvents(new DateTime($from),new DateTime($to));
	}
}
