<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use JsonSerializable;

/**
 * @psalm-api
 * @method int getId()
 * @method void setId(int $value)
 * @method int getCreated()
 * @method void setCreated(int $value)
 * @method int getDeleted()
 * @method void setDeleted(int $value)
 * @method string getDescription()
 * @method void setDescription(string $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method string getTitle()
 * @method void setTitle(string $value)
 * @method string getTitleExt()
 * @method void setTitleExt(string $value)
 */

class PollGroup extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_groups';
	public const RELATION_TABLE = 'polls_groups_polls';
	public const CONCAT_SEPARATOR = ',';

	// schema columns
	public $id = null;
	protected int $created = 0;
	protected int $deleted = 0;
	protected ?string $description = '';
	protected ?string $owner = '';
	protected string $title = '';
	protected string $titleExt = '';
	// joined polls
	protected ?string $pollIds = '';

	public function __construct() {
	}

	/**
	 * @return int[]
	 *
	 * @psalm-return list<int>
	 */
	public function getPollIds(): array {
		if (!$this->pollIds) {
			return [];
		}
		return array_map('intval', explode(self::CONCAT_SEPARATOR, $this->pollIds));
	}

	public function setPollIds(array $pollIds): void {
		$this->pollIds = implode(self::CONCAT_SEPARATOR, $pollIds);
	}

	public function hasPoll(int $pollId): bool {
		$polls = $this->getPollIds();
		return in_array($pollId, $polls, true);
	}

	// alias of getOwner()
	public function getUserId(): string {
		return $this->getOwner();
	}

	// alias of setOwner($value)
	public function setUserId(string $userId): void {
		$this->setOwner($userId);
	}

	/**
	 * @return array
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'created' => $this->getCreated(),
			'deleted' => $this->getDeleted(),
			'description' => $this->getDescription(),
			'owner' => $this->getUser(),
			'title' => $this->getTitle(),
			'titleExt' => $this->getTitleExt(),
			'pollIds' => $this->getPollIds(),
		];
	}
}
