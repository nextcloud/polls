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
		$events= [];

		foreach ($this->calendars as $calendar) {
			$found = $calendar->search('' ,['SUMMARY'], ['timerange' => ['start' => $from, 'end' => $to]]);
			if (count($found) > 0) {
				$events[] = [
					'name'  => $calendar->getDisplayName(),
					'key' => $calendar->getKey(),
					'displayColor' => $calendar->getDisplayColor(),
					'permissions' => $calendar->getPermissions(),
					'events' => $calendar->search('' ,['SUMMARY'], ['timerange' => ['start' => $from, 'end' => $to]])
				];
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
	public function getCalendarsTest($from, $to) {
		return $this->getEvents(new DateTime($from),new DateTime($to));
	}
}
