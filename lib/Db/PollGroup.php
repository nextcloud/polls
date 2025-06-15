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
	public const CONCAT_SEPARATOR = ',';

	// schema columns
	public $id = null;
	protected int $created = 0;
	protected int $deleted = 0;
	protected ?string $description = '';
	protected ?string $owner = '';
	protected string $title = '';
	protected string $titleExt = '';
	protected string $polls = '';

	public function __construct() {
	}

	/**
	 * @return int[]
	 *
	 * @psalm-return list<int>
	 */
	public function getPolls(): array {
		if ($this->polls === '') {
			return [];
		}
		$polls = explode(self::CONCAT_SEPARATOR, $this->polls);
		return array_map('intval', $polls);
	}

	public function setPolls(array $polls): void {
		$this->polls = implode(self::CONCAT_SEPARATOR, $polls);
	}

	public function addPoll(int $pollId): void {
		$polls = $this->getPolls();
		if (!in_array($pollId, $polls, true)) {
			$polls[] = $pollId;
			$this->setPolls($polls);
		}
	}

	public function removePoll(int $pollId): void {
		$polls = $this->getPolls();
		if (($key = array_search($pollId, $polls, true)) !== false) {
			unset($polls[$key]);
			$this->setPolls(array_values($polls));
		}
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
			'polls' => $this->getPolls(),
		];
	}
}
