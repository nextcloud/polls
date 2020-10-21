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

namespace OCA\Polls\Model;
use \OCP\Calendar\ICalendar;

class CalendarEvent implements \JsonSerializable {

	/** @var Array */
	protected $calDav;

	/** @var Array */
	protected $event;

	/** @var ICalendar */
	protected $calendar;

	/**
	 * CalendarEvent constructor.
	 * @param Array $calDav
	 * @param ICalendar $calendar
	 */
	public function __construct(
		$calDav,
		$calendar
	) {
		$this->calDav = $calDav;
		$this->calendar = $calendar;
		$this->event = $this->calDav['objects'][0];
	}


	/**
	 * getAllDay
	 * @return string
	 */
	public function getAllDay() {
		if ($this->getEnd() - $this->getStart() === 86400) {
			return $this->event['DTSTART'][0]->format('Y-m-d');
		} else {
			return '';
		}
	}

	/**
	 * getCalendarName
	 * @return string
	 */
	public function getCalendarName() {
		return $this->calendar->getDisplayName();
	}

	/**
	 * getCalendarKey
	 * @return string
	 */
	public function getCalendarKey() {
		return $this->calendar->getKey();
	}

	/**
	 * getDisplayColor
	 * @return string
	 */
	public function getDisplayColor() {
		return $this->calendar->getDisplayColor();
	}

	/**
	 * getId
	 * @return int
	 */
	public function getId() {
		return $this->calDav['id'];
	}

	/**
	 * getUID
	 * @return string
	 */
	public function getUID() {
		return $this->event['UID'][0];
	}

	/**
	 * getSummary
	 * @return string
	 */
	public function getSummary() {
		return $this->event['SUMMARY'][0];
	}

	/**
	 * getDescription
	 * @return string
	 */
	public function getDescription() {
		if (isset($this->event['DESCRIPTION'][0])) {
			return $this->event['DESCRIPTION'][0];
		} else {
			return '';
		}
	}

	/**
	 * getLocation
	 * @return string
	 */
	public function getLocation() {
		if (isset($this->event['LOCATION'][0])) {
			return $this->event['LOCATION'][0];
		} else {
			return '';
		}
	}

	/**
	 * getStart
	 * @return int
	 */
	public function getStart() {
		if (isset($this->event['DTSTART'][0])) {
			return $this->event['DTSTART'][0]->getTimestamp();
		} else {
			return 0;
		}
	}

	/**
	 * getEnd
	 * @return int
	 */
	public function getEnd() {
		if (isset($this->event['DTEND'][0])) {
			return $this->event['DTEND'][0]->getTimestamp();
		} else {
			return 0;
		}
	}

	/**
	 * getStatus
	 * @return string
	 */
	public function getStatus() {
		if (isset($this->event['STATUS'][0])) {
			return $this->event['STATUS'][0];
		} else {
			return '';
		}
	}

	/**
	 * getCalDav
	 * @return
	 */
	public function getCalDav() {
		return $this->calDav;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return	[
			'id'			=> $this->getId(),
			'UID'			=> $this->getUID(),
			'calendarKey'	=> $this->getCalendarKey(),
			'calendarName'	=> $this->getCalendarName(),
			'displayColor'	=> $this->getDisplayColor(),
			'allDay'		=> $this->getAllDay(),
			'description'	=> $this->getDescription(),
			'end'			=> $this->getEnd(),
			'location'		=> $this->getLocation(),
			'start'			=> $this->getStart(),
			'status'		=> $this->getStatus(),
			'summary'		=> $this->getSummary(),
			'calDav'		=> $this->getCalDav(),
		];
	}
}
