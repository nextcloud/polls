<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\Settings;

use OCA\Polls\UserSession;
use OCP\IAppConfig;

class SystemSettings {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private IAppConfig $appConfig,
		private UserSession $userSession,
	) {
	}
	/** Getters for core settings regarding share creation */
	/**
	 * Permission to create shares is controlled by core settings
	 */
	public function getShareCreateAllowed(): bool {
		if (!$this->userSession->getIsLoggedIn()) {
			// only logged in users can create shares
			return false;
		}

		// first check group exception mode
		$groupExceptionMode = $this->getCoreLimitSharingMode();

		if ($groupExceptionMode === 'off') {
			// no group exceptions are set, allow share creation
			return true;
		}

		// get group exceptions
		$exceptionGroups = $this->getCoreLimitSharingGroups();

		if ($groupExceptionMode === 'denyGroup') {
			// exception mode is 'Exclude some Groups from sharing'
			// if user is in exception group, deny share creation
			return !$this->userSession->getCurrentUser()->getIsInGroupArray($exceptionGroups);
		}

		// exception mode is 'Limit sharing to some groups'
		// if user is in exception group, allow share creation
		return $this->userSession->getCurrentUser()->getIsInGroupArray($exceptionGroups);
	}

	/**
	 * Get share group exception mode
	 * @return 'open'|'closed'|'archived'
	 * @psalm-return 'denyGroup'|'allowGroup'|'off'
	 * Take value from the core setting 'shareapi_exclude_groups' and translate
	 * 'yes' => 'denyGroup' ('Exclude some groups from sharing') existing groups are handeled as deny groups
	 * 'allow' => 'allowGroup' (Limit sharing to some groups) existing groups are handeled as allow groups
	 * default => 'off' (Allow sharing for everyone) or setting absent - sharing is allowed for everyone
	 */
	private function getCoreLimitSharingMode(): string {
		$excludedMode = $this->appConfig->getValueString('core', 'shareapi_exclude_groups', '');
		return match ($excludedMode) {
			'yes' => 'denyGroup',
			'allow' => 'allowGroup',
			default => 'off',
		};
	}

	/**
	 * Get core setting 'shareapi_exclude_groups_list', subsetting of 'Limit sharing to some groups'
	 * Lists Groups, which are either denied or allowed to creating shares (depending on )
	 * @return array
	 */
	private function getCoreLimitSharingGroups(): array {
		return $this->appConfig->getValueArray('core', 'shareapi_exclude_groups_list', []);
	}

	/** Getters for core settings regarding external link creation */
	/**
	 * Is creation of external links via email allowed for the current user?
	 * @psalm-return bool
	 * @return bool
	 */
	public function getExternalShareCreationAllowed(): bool {
		if ($this->getCoreExternalShareCreationMode() === 'off') {
			// external share creation is disabled
			return false;
		}

		$excludedGroups = $this->getCoreExternalShareCreationGroups();
		if ($excludedGroups) {
			// if user is in exception group, disallow external share creation
			return !$this->userSession->getCurrentUser()->getIsInGroupArray($excludedGroups);
		}
		return true;
	}

	/**
	 * Get external share creation mode
	 * @return string
	 * @psalm-return 'denyGroup'|'off'
	 * Take value from the core setting 'shareapi_allow_links' and translate
	 * 'no' => 'off' (Creating external links is disabled) or setting absent
	 * 'yes' => 'denyGroup' ('Creating external links is enablede, but groups may be excluded')
	 * default => 'denyGroup' System default
	 */
	private function getCoreExternalShareCreationMode(): string {
		$excludedMode = $this->appConfig->getValueString('core', 'shareapi_allow_links', '');
		return match ($excludedMode) {
			'no' => 'off',
			'yes' => 'denyGroup',
			default => 'denyGroup',
		};
	}

	/**
	 * Get core setting 'shareapi_allow_links_exclude_groups', subsetting of 'Allow users to share via link and emails'
	 * Lists Groups, which are excluded from creating external link shares
	 * @return array
	 */
	private function getCoreExternalShareCreationGroups(): array {
		return $this->appConfig->getValueArray('core', 'shareapi_allow_links_exclude_groups', []);
	}
}
