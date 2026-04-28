<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls;

use OCA\Polls\Db\Poll;
use OCP\Capabilities\ICapability;

/**
 * @psalm-import-type PollsCapabilities from \OCA\Polls\ResponseDefinitions
 */
class Capabilities implements ICapability {
	/**
	 * @return array{polls: PollsCapabilities}
	 * @psalm-suppress ImplementedReturnTypeMismatch
	 */
	public function getCapabilities(): array {
		return [
			'polls' => [
				'pollType' => [Poll::TYPE_DATE, Poll::TYPE_TEXT],
				'voteVariant' => [Poll::VARIANT_SIMPLE],
				'access' => [Poll::ACCESS_PRIVATE, Poll::ACCESS_OPEN],
				'showResults' => [Poll::SHOW_RESULTS_ALWAYS, Poll::SHOW_RESULTS_CLOSED, Poll::SHOW_RESULTS_NEVER],
			],
		];
	}
}
