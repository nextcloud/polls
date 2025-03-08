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
use OCA\Polls\UserSession;

class PreferencesService {

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private PreferencesMapper $preferencesMapper,
		private Preferences $preferences,
		private UserSession $userSession,
	) {
		$this->load();
	}

	public function load(): void {
		try {
			$this->preferences = $this->preferencesMapper->find($this->userSession->getCurrentUserId());
		} catch (Exception $e) {
			$this->preferences = new Preferences;
		}
	}

	public function get(): Preferences {
		return $this->preferences;
	}

	/**
	 * Write references
	 */
	public function write(array $preferences): Preferences {
		if (!$this->userSession->getCurrentUserId()) {
			throw new NotAuthorizedException();
		}

		$preferences = $this->tidyPreferences($preferences);
		$this->preferences->setPreferences(json_encode($preferences));
		$this->preferences->setTimestamp(time());
		$this->preferences->setUserId($this->userSession->getCurrentUserId());

		if ($this->preferences->getId() > 0) {
			return $this->preferencesMapper->update($this->preferences);
		} else {
			return $this->preferencesMapper->insert($this->preferences);

		}
	}


	/**
	 * Tidy preferences
	 * @param array $preferences
	 */
	private function tidyPreferences(array $preferences): array {

		// remove old properties (checkCalendarsBefore)
		if (isset($preferences['checkCalendarsBefore'])) {
			if (isset($preferences['checkCalendarsHoursBefore'])) {
				unset($preferences['checkCalendarsBefore']);
			} else {
				$preferences['checkCalendarsHoursBefore'] = $preferences['checkCalendarsBefore'];
				unset($preferences['checkCalendarsBefore']);
			}
		}
		// remove old properties (checkCalendarsAfter)
		if (isset($preferences['checkCalendarsAfter'])) {
			if (isset($preferences['checkCalendarsHoursAfter'])) {
				unset($preferences['checkCalendarsAfter']);
			} else {
				$preferences['checkCalendarsHoursAfter'] = $preferences['checkCalendarsAfter'];
				unset($preferences['checkCalendarsAfter']);
			}
		}

		return $preferences;
	}
}
