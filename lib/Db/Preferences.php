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

	public const DEPRECATED_SETTINGS = [
		'checkCalendars',
		'checkCalendarUris',
		'checkCalendarsFullUris',
		'checkCalendarsBefore',
		'checkCalendarsAfter',
	];

	public const DEFAULT_SETTINGS = [
		'useCommentsAlternativeStyling' => false,
		'useAlternativeStyling' => false,
		'calendarPeek' => false,
		'checkCalendarsUris' => [],
		'checkCalendarsHoursBefore' => 0,
		'checkCalendarsHoursAfter' => 0,
		'defaultViewTextPoll' => 'table-view',
		'defaultViewDatePoll' => 'table-view',
		'pollCombo' => [],
		'relevantOffset' => 30,
	];

	// schema columns
	public $id = 0;
	protected string $userId = '';
	protected int $timestamp = 0;
	protected ?string $preferences = '';

	public function __construct() {
		$this->addType('timestamp', 'integer');

		// initialize with default values
		$this->setUserSettings(self::DEFAULT_SETTINGS);
	}

	public function setUserSettings(array $settings): void {
		$this->setPreferences(json_encode($settings));
	}

	public function getUserSettings(): array {
		return json_decode($this->getPreferences() ?? '', true);
	}

	public function getCheckCalendarsHoursBefore(): int {
		if (isset($this->getUserSettings()['checkCalendarsHoursBefore'])) {
			return intval($this->getUserSettings()['checkCalendarsHoursBefore']);
		}

		// in case old property name is used, return the value
		if (isset($this->getUserSettings()['checkCalendarsBefore'])) {
			return intval($this->getUserSettings()['checkCalendarsBefore']);
		}
		return 0;
	}

	public function getCheckCalendarsHoursAfter(): int {
		if (isset($this->getUserSettings()['checkCalendarsHoursAfter'])) {
			return intval($this->getUserSettings()['checkCalendarsHoursAfter']);
		}

		// in case old property name is used, return the value
		if (isset($this->getUserSettings()['checkCalendarsAfter'])) {
			return intval($this->getUserSettings()['checkCalendarsAfter']);
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
			'preferences' => $this->getUserSettings(),
		];
	}
}
