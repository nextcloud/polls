<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use Exception;
use JsonSerializable;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\UserBase;

/**
 * @psalm-suppress UnusedProperty
 * @method int getId()
 * @method void setId(int $value)
 * @method ?int getPollId()
 * @method void setPollId(?int $value)
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
 * @method int getConfidential()
 * @method void setConfidential(int $value)
 * @method string getRecipient()
 * @method void setRecipient(string $value)
 */
class Comment extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_comments';
	public const CONFIDENTIAL_NO = 0;
	public const CONFIDENTIAL_YES = 1;

	// schema columns
	public $id = null;
	protected ?int $pollId = null;
	protected string $userId = '';
	protected ?string $comment = null;
	protected int $timestamp = 0;
	protected int $deleted = 0;
	protected int $confidential = 0;
	protected ?string $recipient = null;

	// computed attributes
	protected int $parent = 0;

	public function __construct() {
		$this->addType('pollId', 'integer');
		$this->addType('timestamp', 'integer');
		$this->addType('deleted', 'integer');
		$this->addType('confidential', 'integer');
	}

	public function getRecipientUser() : ?UserBase {
		if ($this->getRecipient() === '' || $this->getRecipient() === null) {
			return null;
		}

		try {
			/* @var UserMapper $userMapper */
			$userMapper = (Container::queryClass(UserMapper::class));
			$user = $userMapper->getParticipant($this->getRecipient(), $this->getPollId());
		} catch (Exception $e) {
			return null;
		}

		return $user;
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
			'confidential' => $this->getConfidential(),
			'parent' => $this->getParent(),
			'deleted' => $this->getDeleted(),
			'user' => $this->getUser(),
			'recipient' => $this->getRecipientUser(),
		];
	}

}
