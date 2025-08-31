<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use Exception;
use OCA\Polls\Db\Preferences;
use OCA\Polls\Db\PreferencesMapper;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\UserSession;
use OCP\Calendar\IManager as CalendarManager;

class PreferencesService {

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private PreferencesMapper $preferencesMapper,
		private Preferences $preferences,
		private UserSession $userSession,
		private CalendarManager $calendarManager,
	) {
		$this->load();
	}

	public function load(): void {
		try {
			$this->preferences = $this->preferencesMapper->find($this->userSession->getCurrentUserId());

			if (!$this->preferences->getUserSettings()) {
				$this->preferences->setUserSettings(Preferences::DEFAULT_SETTINGS);
				throw new NotFoundException('load: No preferences array found');
			}
			$migration = $this->convertCalendars();
			$migration = $this->adjustTimeToleranceProperties() || $migration;
			$migration = $this->removeDeprecatedProperties() || $migration;

			if ($migration) {
				// remove deprecated properties after migrating them
				$this->preferences->setTimestamp(time());
				$this->preferences = $this->preferencesMapper->update($this->preferences);
			}

		} catch (Exception $e) {
			$this->preferences = new Preferences;
			$this->preferences->setUserId($this->userSession->getCurrentUserId());
			$this->preferences->setTimestamp(time());
			$this->preferences = $this->preferencesMapper->insert($this->preferences);
		}
	}

	public function get(): Preferences {
		return $this->preferences;
	}

	/**
	 * Write references
	 */
	public function write(array $settings): Preferences {
		if (!$this->userSession->getCurrentUserId()) {
			throw new NotAuthorizedException();
		}

		$this->preferences->setUserSettings($settings);
		$this->preferences->setTimestamp(time());

		if ($this->preferences->getId() > 0) {
			$this->preferences = $this->preferencesMapper->update($this->preferences);
			return $this->preferences;
		} else {
			$this->preferences->setUserId($this->userSession->getCurrentUserId());
			$this->preferences = $this->preferencesMapper->insert($this->preferences);
			return $this->preferences;
		}
	}

	private function removeDeprecatedProperties(): bool {
		$migration = false;
		$settings = $this->preferences->getUserSettings();

		foreach (Preferences::DEPRECATED_SETTINGS as $property) {
			if (isset($settings[$property])) {
				unset($settings[$property]);
				$migration = true;
			}
		}
		$this->preferences->setUserSettings($settings);
		return $migration;
	}

	/**
	 * Convert calendar keys to uris
	 */
	private function convertCalendars(): bool {
		$settings = $this->preferences->getUserSettings();

		// only convert, if checkCalendars is set
		if (!isset($settings['checkCalendars'])) {
			return false;
		}

		$principalUri = $this->userSession->getCurrentUser()->getPrincipalUri();

		if (empty($principalUri)) {
			return false;
		}

		$calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri);

		$settings['checkCalendarsUris'] = [];

		foreach ($settings['checkCalendars'] as $calendarKey) {
			foreach ($calendars as $calendar) {
				if ($calendar->getKey() === $calendarKey) {
					$settings['checkCalendarsUris'][] = $calendar->getUri();
				}
			}
		}
		$this->preferences->setUserSettings($settings);
		return true;
	}

	/**
	 * Adjust time tolerance properties
	 */
	private function adjustTimeToleranceProperties(): bool {
		$migration = false;
		$settings = $this->preferences->getUserSettings();
		// migrate checkCalendarsBefore
		if (isset($settings['checkCalendarsBefore'])
			&& !isset($settings['checkCalendarsHoursBefore'])) {
			$settings['checkCalendarsHoursBefore'] = $settings['checkCalendarsBefore'];
			$migration = true;
		}
		// remove old properties (checkCalendarsAfter)
		if (isset($settings['checkCalendarsAfter']) && !isset($settings['checkCalendarsHoursAfter'])) {
			$settings['checkCalendarsHoursAfter'] = $settings['checkCalendarsAfter'];
			$migration = true;
		}
		$this->preferences->setUserSettings($settings);
		return $migration;
	}
}
