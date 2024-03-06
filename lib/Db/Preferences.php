<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

namespace OCA\Polls\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @psalm-suppress UnusedProperty
 * @method int getId()
 * @method void setId(int $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method string getTimestamp()
 * @method void setTimestamp(int $value)
 * @method string getPreferences()
 * @method void setPreferences(string $value)
 */
class Preferences extends Entity implements JsonSerializable {
	public const TABLE = 'polls_preferences';

	public const DEFAULT = [
		'useCommentsAlternativeStyling' => false,
		'useAlternativeStyling' => false,
		'calendarPeek' => false,
		'checkCalendars' => [],
		'checkCalendarsBefore' => 0,
		'checkCalendarsAfter' => 0,
		'defaultViewTextPoll' => 'table-view',
		'defaultViewDatePoll' => 'table-view',
		'performanceThreshold' => 1000,
		'pollCombo' => [],
		'relevantOffset' => 30,
	];

	// schema columns
	public $id = 0;
	protected string $userId = '';
	protected int $timestamp = 0;
	protected ?string $preferences = '';

	public function __construct() {
		$this->addType('timestamp', 'int');

		// initialize with default values
		$this->setPreferences(json_encode(self::DEFAULT));
	}

	public function getPreferences_decoded(): mixed {
		return json_decode($this->getPreferences());
	}

	/**
	 * getRelevantOffset - Offset for relevant polls in days
	 */
	public function getRelevantOffset(): int {
		if (isset($this->getPreferences_decoded()->relevantOffset)) {
			return intval($this->getPreferences_decoded()->relevantOffset);
		}
		return 30;
	}

	/**
	 * getRelevantOffsetTimestamp - Offset for relevant polls in seconds (unix timestamp)
	 */
	public function getRelevantOffsetTimestamp(): int {
		return $this->getRelevantOffset() * 24 * 60 * 60;
	}

	public function getCheckCalendarsBefore(): int {
		if (isset($this->getPreferences_decoded()->checkCalendarsBefore)) {
			return intval($this->getPreferences_decoded()->checkCalendarsBefore);
		}
		return 0;
	}
	
	public function getCheckCalendarsAfter(): int {
		if (isset($this->getPreferences_decoded()->checkCalendarsAfter)) {
			return intval($this->getPreferences_decoded()->checkCalendarsAfter);
		}
		return 0;
	}

	/**
	 * @return array
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {
		return [
			'preferences' => json_decode((string) $this->preferences),
		];
	}
}
