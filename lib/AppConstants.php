<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls;

/**
 * @deprecated Use Application::* constants directly
 */
abstract class AppConstants {
	/** @var string */
	public const SESSION_KEY_CRON_JOB = 'ncPollsCronJob';
}
