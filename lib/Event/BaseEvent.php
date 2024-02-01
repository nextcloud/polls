<?php
/*
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Helper\Container;
use OCP\EventDispatcher\Event;

abstract class BaseEvent extends Event {
	protected ?string $activityObjectType = null;
	protected ?string $eventId = null;
	protected array $activitySubjectParams = [];
	protected bool $log = true;
	protected Poll $poll;
	protected UserMapper $userMapper;


	public function __construct(
		protected Poll|Comment|Share|Option|Vote $eventObject,
	) {
		parent::__construct();
		$this->poll = Container::queryPoll($this->getPollId());
		$this->userMapper = Container::queryClass(UserMapper::class);

		// Default
		$this->activitySubjectParams['pollTitle'] = [
			'type' => 'highlight',
			'id' => $this->eventObject->getPollId(),
			'name' => $this->poll->getTitle(),
			'link' => $this->poll->getVoteUrl(),
		];
	}

	public function getPollId(): int {
		return $this->eventObject->getPollId();
	}

	public function getPollTitle(): string {
		return $this->poll->getTitle();
	}

	public function getPollOwner(): string {
		return $this->poll->getOwner();
	}

	public function getActor(): ?string {
		return $this->userMapper->getCurrentUser()?->getId() ?? $this->eventObject->getUserId();
	}

	public function getLogId(): string {
		if ($this->log && $this->eventId) {
			return $this->eventId;
		}
		return '';
	}

	public function getNotification(): array {
		return [];
	}

	public function getActivityObjectType(): ?string {
		return $this->activityObjectType;
	}

	public function getActivityObjectId(): int {
		if ($this->activityObjectType === 'poll') {
			return $this->eventObject->getPollId();
		}
		return $this->eventObject->getId();
	}

	public function getActivityType(): ?string {
		return $this->eventId;
	}

	public function getActivitySubjectParams(): array {
		return $this->activitySubjectParams;
	}
}
