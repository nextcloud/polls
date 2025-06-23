<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @psalm-suppress UnusedProperty
 * @method int getId()
 * @method void setId(int $value)
 * @method ?int getPollId()
 * @method void setPollId(?int $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 */
class Subscription extends Entity implements JsonSerializable {
	public const TABLE = 'polls_notif';

	// schema columns
	public $id = null;
	protected ?int $pollId = null;
	protected string $userId = '';

	/** @var Log[] $logEntries */
	protected array $logEntries = [];


	public function __construct() {
		$this->addType('pollId', 'integer');
	}

	/**
	 * @return array
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'pollId' => $this->getPollId(),
			'userId' => $this->getUserId(),
		];
	}

	/**
	 * @param Log[] $logs Array of logs for notifications
	 */
	public function setNotifyLogs(array $logs) : void {
		$pollId = $this->getPollId();
		$this->logEntries = array_filter($logs, function ($log) use ($pollId) {
			return $log->getPollId() === $pollId;
		});
	}

	/**
	 * @return Log[]
	 */
	public function getNotifyLogs() : array {
		return $this->logEntries;
	}
}
