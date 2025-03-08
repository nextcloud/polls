<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Settings;

use OCP\Activity\ISetting;
use OCP\IL10N;

/**
 * @psalm-suppress UnusedClass
 */
class ActivitySettings implements ISetting {
	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		protected IL10N $l10n,
	) {
	}

	public function getIdentifier() : string {
		return 'poll';
	}

	public function getName() : string {
		return $this->l10n->t('Events happening inside of a <strong>poll</strong>');
	}

	public function getPriority() : int {
		return 90;
	}

	public function canChangeStream() : bool {
		return true;
	}

	public function isDefaultEnabledStream() : bool {
		return true;
	}

	public function canChangeMail()  : bool {
		return true;
	}

	public function isDefaultEnabledMail() : bool {
		return false;
	}
}
