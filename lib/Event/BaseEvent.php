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

use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Db\Entity;
use OCP\EventDispatcher\Event;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\AppInfo\Application;

abstract class BaseEvent extends Event {
	/** @var string */
	protected $activityObject = '';

	/** @var string */
	protected $activitySubject = '';

	/** @var array */
	protected $activitySubjectParams = [];

	/** @var Entity */
	protected $eventObject;

	/** @var Poll */
	protected $poll;

	/** @var bool */
	protected $log = true;

	public function __construct(
		Entity $eventObject
	) {
		parent::__construct();
		$this->eventObject = $eventObject;
		$this->poll = $this->getContainer()->query(PollMapper::class)->find($this->getPollId());
		$this->activitySubjectParams['pollTitle'] = [
			'type' => 'highlight',
			'id' => $this->eventObject->getPollId(),
			'name' => $this->poll->getTitle(),
			'link' => $this->poll->getVoteUrl(),
		];
	}

	public function getEventObject(): Entity {
		return $this->eventObject;
	}

	public function getPollId(): int {
		return $this->eventObject->getPollId();
	}

	public function getPollUrl(): string {
		return $this->poll->getVoteUrl();
	}

	public function getPollTitle(): string {
		return $this->poll->getTitle();
	}

	public function getPollOwner(): string {
		return $this->poll->getOwner();
	}

	public function getPoll(): Poll {
		return $this->poll;
	}

	public function getActor(): string {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			return \OC::$server->getUserSession()->getUser()->getUID();
		}
		return $this->eventObject->getUserId();
	}

	public function getLogId(): ?string {
		if ($this->log && $this->activitySubject) {
			return $this->activitySubject;
		}
		return '';
	}

	public function getNotification(): array {
		return [];
	}

	public function getActivityId(): string {
		return $this->activitySubject;
	}

	public function getActivityObject(): string {
		return $this->activityObject;
	}

	public function getActivityObjectId(): int {
		if ($this->activityObject === 'poll') {
			return $this->eventObject->getPollId();
		}
		return $this->eventObject->getId();
	}

	public function getActivitySubject(): string {
		return $this->activitySubject;
	}

	public function getActivitySubjectParams(): array {
		return $this->activitySubjectParams;
	}

	protected static function getContainer() : IAppContainer {
		$app = \OC::$server->query(Application::class);
		return $app->getContainer();
	}
}
