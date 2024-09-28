<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\Db\Log;
use OCA\Polls\Db\LogMapper;
use OCA\Polls\UserSession;

class LogService {
	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private LogMapper $logMapper,
		private Log $log,
		private UserSession $userSession,
	) {
	}

	/**
	 * Log poll activity
	 */
	public function setLog(int $pollId, string $messageId, string|null $userId = null): void {
		$this->log = new Log();
		$this->log->setPollId($pollId);
		$this->log->setCreated(time());
		$this->log->setMessageId($messageId);
		$this->log->setUserId($userId ?? $this->userSession->getCurrentUserId());

		$this->logMapper->insert($this->log);
	}
}
