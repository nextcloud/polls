<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Attributes;

use Attribute;

/**
 * Attribute for Cron jobs, if they support manual execution
 */
#[Attribute]
class ManuallyRunnableCronJob {
}
