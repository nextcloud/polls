<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use Exception;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\User\Anon;
use OCA\Polls\Model\UserBase;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\Entity;

/**
 * @psalm-suppress UnusedProperty
 * @method string getUserId()
 * @method ?int getPollId()
 *
 * Joined Attributes
 * @method int getAnonymized()
 * @method string getPollOwnerId()
 * @method string getPollShowResults()
 * @method int getPollExpire()
 * @method string getShareType()
 */

abstract class EntityWithUser extends Entity {
	protected int $anonymized = 0;
	protected string $pollOwnerId = '';
	protected string $pollShowResults = '';
	protected int $pollExpire = 0;
	protected ?string $shareType = '';

	public function __construct() {
		// joined Attributes
		$this->addType('anonymized', 'integer');
		$this->addType('poll_expire', 'integer');
	}

	/**
	 * Is the current user the owner of the entity
	 * @return bool
	 */
	public function getCurrentUserIsEntityUser(): bool {
		$userSession = Container::queryClass(UserSession::class);
		return $userSession->getCurrentUserId() === $this->getUserId();
	}

	private function getEntityAnonymization(): bool {
		if ($this->getCurrentUserIsEntityUser()) {
			// if the current user is the owner of the entity, don't anonymize the entity
			return false;
		}

		if ($this->getAnonymized() < 0) {
			// the poll is anonymized and locked, anonymize the entity
			return true;
		}

		$userSession = Container::queryClass(UserSession::class);
		if ($this->getPollOwnerId() === $userSession->getCurrentUserId()) {
			// if the current user is the poll owner, don't anonymize the entity
			return false;
		}

		if ($this->getShareType() === Share::TYPE_ADMIN) {
			// if the current user is a delegated admin, don't anonymize the entity
			return false;
		}

		if ($this->getAnonymized() > 0) {
			// if the current user is not the poll owner, anonymize the entity
			return true;
		}

		// the poll is not anonymized
		if ($this->getPollShowResults() === Poll::SHOW_RESULTS_NEVER
			|| ($this->getPollShowResults() === Poll::SHOW_RESULTS_CLOSED
				&& !$this->getPollExpire() > time())) {

			// Do not anonymize the poll owner
			return !($this instanceof Poll);
		}

		// in all other cases, don't anonymize the entity
		return false;
	}


	/**
	 * @return UserBase Gets owner of the entity
	 */
	public function getUser(): UserBase {
		if ($this->getEntityAnonymization()) {
			$user = new Anon($this->getUserId());
			return $user;
		}

		$userMapper = (Container::queryClass(UserMapper::class));

		try {
			$pollId = $this->getPollId();
			if ($pollId === null) {
				return $userMapper->getUserFromUserBase($this->getUserId());
			}
			$user = $userMapper->getParticipant($this->getUserId(), $pollId);
			// Get user from userbase
		} catch (Exception $e) {
			// If pollId is not set, we assume that the user is not a participant of a poll
			$user = $userMapper->getUserFromUserBase($this->getUserId());
		}
		return $user;
	}
}
