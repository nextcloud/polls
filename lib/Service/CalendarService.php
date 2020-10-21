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
	 * getEvents - get events from the user's calendars inside given timespan
	 * @NoAdminRequired
	 * @param DateTime $from
	 * @param DateTime $to
	 * @return Array
	 */
	public function getEvents($from, $to) {
		$events = [];
		foreach ($this->calendars as $calendar) {

			// Skip not configured calendars
			if (!in_array($calendar->getKey(), json_decode($this->preferences->getPreferences())->checkCalendars)) {
				continue;
			}

			$foundEvents = $calendar->search('', ['SUMMARY'], ['timerange' => ['start' => $from, 'end' => $to]]);
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
					'summary' => isset($event['objects'][0]['SUMMARY'][0]) ? $event['objects'][0]['SUMMARY'][0] : '',
					'description' => isset($event['objects'][0]['DESCRIPTION'][0]) ? $event['objects'][0]['DESCRIPTION'][0] : '',
					'location' => isset($event['objects'][0]['LOCATION'][0]) ? $event['objects'][0]['LOCATION'][0] : '',
					'eventFrom' => isset($event['objects'][0]['DTSTART'][0]) ? $event['objects'][0]['DTSTART'][0]->getTimestamp() : 0,
					'eventTo' => isset($event['objects'][0]['DTEND'][0]) ? $event['objects'][0]['DTEND'][0]->getTimestamp() : 0,
					'calDav' => $event
				]);
			}
		}
		return $events;
	}

	/**
	 * Get user's calendars
	 * @NoAdminRequired
	 * @return Array
	 */
	public function getCalendars() {
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
