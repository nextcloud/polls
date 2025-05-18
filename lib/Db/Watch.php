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
 * @method int getPollId()
 * @method void setPollId(int $value)
 * @method string getTable()
 * @method void setTable(string $value)
 * @method int getUpdated()
 * @method void setUpdated(int $value)
 * @method string getSessionId()
 * @method void setSessionId(string $value)
 */
class Watch extends Entity implements JsonSerializable {
	public const TABLE = 'polls_watch';
	public const OBJECT_POLLS = 'polls';
	public const OBJECT_VOTES = 'votes';
	public const OBJECT_OPTIONS = 'options';
	public const OBJECT_COMMENTS = 'comments';
	public const OBJECT_SHARES = 'shares';

	// schema columns
	public $id = null;
	protected int $pollId = 0;
	protected string $table = '';
	protected int $updated = 0;
	protected string $sessionId = '';

	public function __construct() {
		$this->addType('pollId', 'integer');
		$this->addType('updated', 'integer');
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
			'table' => $this->getTable(),
			'updated' => $this->getUpdated(),
		];
	}
}
