<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use JsonSerializable;

/**
 * @psalm-suppress UnusedProperty
 * @method int getId()
 * @method void setId(int $value)
 * @method int getPollId()
 * @method void setPollId(int $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method string getComment()
 * @method void setComment(string $value)
 * @method int getTimestamp()
 * @method void setTimestamp(int $value)
 * @method int getDeleted()
 * @method void setDeleted(int $value)
 * @method int getParent()
 * @method void setParent(int $value)
 */
class Comment extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_comments';

	// schema columns
	public $id = null;
	protected int $pollId = 0;
	protected string $userId = '';
	protected ?string $comment = null;
	protected int $timestamp = 0;
	protected int $deleted = 0;

	// computed attributes
	protected int $parent = 0;

	public function __construct() {
		$this->addType('pollId', 'integer');
		$this->addType('timestamp', 'integer');
		$this->addType('deleted', 'integer');
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
			'timestamp' => $this->getTimestamp(),
			'comment' => $this->getComment(),
			'parent' => $this->getParent(),
			'deleted' => $this->getDeleted(),
			'user' => $this->getUser(),
		];
	}

}
