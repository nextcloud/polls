<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		'checkCalendarsHoursBefore' => 0,
		'checkCalendarsHoursAfter' => 0,
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
	protected string|null $preferences = '';

	public function __construct() {
		$this->addType('timestamp', 'int');

		// initialize with default values
		$this->setPreferences(json_encode(self::DEFAULT));
	}

	public function getPreferences_decoded(): mixed {
		return json_decode($this->getPreferences() ?? '');
	}

	public function getCheckCalendarsHoursBefore(): int {
		if (isset($this->getPreferences_decoded()->checkCalendarsHoursBefore)) {
			return intval($this->getPreferences_decoded()->checkCalendarsHoursBefore);
		}

		// in case old property name is used, return the value
		if (isset($this->getPreferences_decoded()->checkCalendarsBefore)) {
			return intval($this->getPreferences_decoded()->checkCalendarsBefore);
		}
		return 0;
	}
	
	public function getCheckCalendarsHoursAfter(): int {
		if (isset($this->getPreferences_decoded()->checkCalendarsHoursAfter)) {
			return intval($this->getPreferences_decoded()->checkCalendarsHoursAfter);
		}

		// in case old property name is used, return the value
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
			'preferences' => json_decode((string)$this->preferences),
		];
	}
}
