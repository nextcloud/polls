<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Watch;
use OCA\Polls\Db\WatchMapper;
use OCA\Polls\Exceptions\NoUpdatesException;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;

class WatchService {

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private AppSettings $appSettings,
		private PollMapper $pollMapper,
		private UserSession $userSession,
		private Watch $watch,
		private WatchMapper $watchMapper,
	) {
	}

	/**
	 * Watch poll for updates
	 */
	public function watchUpdates(int $pollId, ?int $offset = null): array {
		if ($pollId) {
			$this->pollMapper->get($pollId, withRoles: true)->request(Poll::PERMISSION_POLL_VIEW);
		}

		$start = time();
		$timeout = 30;
		$offset = $offset ?? $start;

		if ($this->appSettings->getUpdateType() === AppSettings::SETTING_UPDATE_TYPE_LONG_POLLING) {
			while (empty($updates) && time() <= $start + $timeout) {
				sleep(1);
				$updates = $this->getUpdates($pollId, $offset);
			}
		} else {
			$updates = $this->getUpdates($pollId, $offset);
		}

		if (empty($updates)) {
			throw new NoUpdatesException;
		}

		return $updates;
	}

	/**
	 * @return Watch[]
	 */
	private function getUpdates(int $pollId, int $offset): array {
		try {
			return $this->watchMapper->findUpdatesForPollId($pollId, $offset);
		} catch (DoesNotExistException $e) {
			return [];
		}
	}

	public function writeUpdate(int $pollId, string $table): void {
		$this->watch = new Watch();
		$this->watch->setPollId($pollId);
		$this->watch->setTable($table);
		$this->watch->setSessionId($this->userSession->getClientIdHashed());

		try {
			$this->watch = $this->watchMapper->insert($this->watch);
		} catch (Exception $e) {
			if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $e;
			}
			$this->watch = $this->watchMapper->findForPollIdAndTable($pollId, $table);
		}

		$this->watch->setUpdated(time());
		$this->watchMapper->update($this->watch);
	}
}
