<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollGroup;
use OCA\Polls\Db\PollGroupMapper;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Event\PollUpdatedEvent;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\InsufficientAttributesException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;

class PollGroupService {

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private AppSettings $appSettings,
		private IEventDispatcher $eventDispatcher,
		private PollMapper $pollMapper,
		private UserSession $userSession,
		private PollGroupMapper $pollGroupMapper,
	) {
	}

	public function listPollGroups(): array {
		return $this->pollGroupMapper->list();
	}

	public function updatePollGroup(
		int $pollGroupId,
		string $name,
		string $titleExt,
		?string $description,
	): PollGroup {
		try {
			$pollGroup = $this->pollGroupMapper->find($pollGroupId);
			if ($pollGroup->getOwner() !== $this->userSession->getCurrentUserId()) {
				throw new ForbiddenException('You do not have permission to edit this poll group');
			}
			$pollGroup->setName($name);
			$pollGroup->setTitleExt($titleExt);
			$pollGroup->setDescription($description);

			$pollGroup = $this->pollGroupMapper->update($pollGroup);
			return $pollGroup;
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Poll group not found');
		}
	}
	public function addPollToPollGroup(
		int $pollId,
		?int $pollGroupId = null,
		?string $pollGroupName = null,
	): PollGroup {
		$poll = $this->pollMapper->get($pollId, withRoles: true);
		$poll->request(Poll::PERMISSION_POLL_EDIT);

		// Without poll group id, we create a new poll group
		if ($pollGroupId === null
			&& $pollGroupName !== null
			&& $pollGroupName !== ''
		) {
			if (!$this->appSettings->getPollCreationAllowed()) {
				// If poll creation is disabled, creating a poll group is also disabled
				throw new ForbiddenException('Poll group creation is disabled');
			}

			// Create new poll group
			$pollGroup = new PollGroup();
			$pollGroup->setName($pollGroupName);
			$pollGroup->setOwner($this->userSession->getCurrentUserId());
			$pollGroup->setCreated(time());
			$pollGroup = $this->pollGroupMapper->add($pollGroup);

		} elseif ($pollGroupId !== null) {
			$pollGroup = $this->pollGroupMapper->find($pollGroupId);

		} else {
			throw new InsufficientAttributesException('An existing poll group id must be provided or a new poll group name must be given.');
		}

		if (!$pollGroup->hasPoll($pollId)) {
			try {
				$this->pollGroupMapper->addPollToGroup($pollId, $pollGroup->getId());
			} catch (Exception $e) {
				if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					// Poll is already member of this group
				} else {
					throw $e;
				}
			}

			$this->eventDispatcher->dispatchTyped(new PollUpdatedEvent($poll));
		}

		return $this->pollGroupMapper->find($pollGroup->getId());
	}

	public function removePollFromPollGroup(
		int $pollId,
		int $pollGroupId,
	): ?PollGroup {
		$poll = $this->pollMapper->get($pollId, withRoles: true);
		$poll->request(Poll::PERMISSION_POLL_EDIT);

		$pollGroup = $this->pollGroupMapper->find($pollGroupId);

		if ($pollGroup->hasPoll($pollId)) {
			$this->pollGroupMapper->removePollFromGroup($pollId, $pollGroupId);
			$this->eventDispatcher->dispatchTyped(new PollUpdatedEvent($poll));
		} else {
			throw new NotFoundException('Poll not found in group');
		}

		$this->pollGroupMapper->tidyPollGroups();
		try {
			$pollGroup = $this->pollGroupMapper->find($pollGroupId);
		} catch (DoesNotExistException $e) {
			// Poll group was deleted, return null
			return null;
		}
		return $pollGroup;
	}
}
