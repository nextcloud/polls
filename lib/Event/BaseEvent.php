<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Comment;
use OCA\Polls\Db\Option;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Helper\Container;
use OCA\Polls\UserSession;
use OCP\EventDispatcher\Event;

abstract class BaseEvent extends Event {
	protected ?string $activityObjectType = 'poll';
	protected ?string $eventId = null;
	protected array $activitySubjectParams = [];
	protected bool $log = true;
	protected Poll $poll;
	protected UserMapper $userMapper;
	protected UserSession $userSession;


	public function __construct(
		protected Poll|Comment|Share|Option|Vote $eventObject,
	) {
		parent::__construct();
		$this->poll = Container::queryPoll($this->getPollId());
		$this->userMapper = Container::queryClass(UserMapper::class);
		$this->userSession = Container::queryClass(UserSession::class);

		// Default
		$this->activitySubjectParams['poll'] = [
			'type' => 'highlight',
			'id' => (string)$this->eventObject->getPollId(),
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

	public function getActor(): string {
		if ($this->userSession->getCurrentUserId() !== '') {
			return $this->userSession->getCurrentUserId();
		}
		return $this->eventObject->getUserId();
	}

	public function getLogId(): string {
		if ($this->log && boolval($this->eventId)) {
			return (string)$this->eventId;
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
