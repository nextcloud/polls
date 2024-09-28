<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Polls\Activity;

use OCA\Polls\AppConstants;
use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

/**
 * @psalm-suppress UnusedClass
 */
class PollChanges implements IFilter {
	public function __construct(
		protected IL10N $l10n,
		protected IURLGenerator $urlGenerator,
	) {
	}

	public function getIdentifier() : string {
		return AppConstants::APP_ID;
	}

	public function getName() : string {
		return $this->l10n->t('Poll changes');
	}

	public function getIcon() : string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath(AppConstants::APP_ID, 'polls.svg'));
	}

	public function getPriority() : int {
		return 70;
	}

	public function allowedApps() : array {
		return [AppConstants::APP_ID];
	}

	public function filterTypes(array $types) : array {
		return ['poll_add', 'vote_set'];
	}
}
