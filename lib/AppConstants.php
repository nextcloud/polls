<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls;

abstract class AppConstants {
	/** @var string */
	public const APP_ID = 'polls';
	/** @var string */
	public const CLIENT_ID = 'ncPollsClientId';
	/** @var string */
	public const CLIENT_TZ = 'ncPollsClientTimeZone';
	/** @var string */
	public const SESSION_KEY_CRON_JOB = 'ncPollsCronJob';

}
