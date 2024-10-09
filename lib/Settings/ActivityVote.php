<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Settings;

/**
 * @psalm-suppress UnusedClass
 */
class ActivityVote extends ActivitySettings {
	public function getIdentifier() : string {
		return 'vote_set';
	}

	public function getName() : string {
		return $this->l10n->t('Someone voted inside a <strong>poll</strong>');
	}
}
