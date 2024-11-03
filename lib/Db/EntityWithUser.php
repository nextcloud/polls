<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use OCA\Polls\Helper\Container;
use OCA\Polls\Model\UserBase;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\Entity;

/**
 * @psalm-suppress UnusedProperty
 * @method string getUserId()
 * @method int getPollId()
 *
 * Joined Attributes
 * @method int getAnonymized()
 * @method string getPollOwnerId()
 * @method string getPollShowResults()
 * @method int getPollExpire()
 */

abstract class EntityWithUser extends Entity {
	protected int $anonymized = 0;
	protected string $pollOwnerId = '';
	protected string $pollShowResults = '';
	protected int $pollExpire = 0;

	public const ANON_FULL = 'anonymous';
	public const ANON_PRIVACY = 'privacy';
	public const ANON_NONE = 'ful_view';

	public function __construct() {
		// joined Attributes
		$this->addType('anonymized', 'integer');
		$this->addType('poll_expire', 'integer');
	}
	/**
	 * Anonymized the user completely (ANON_FULL) or just strips out personal information
	 */
	public function getAnonymizeLevel(): string {
		$currentUserId = Container::queryClass(UserSession::class)->getCurrentUserId();
		// Don't censor for poll owner or it is the current user's entity
		if ($this->getPollOwnerId() === $currentUserId || $this->getUserId() === $currentUserId) {
			return self::ANON_NONE;
		}

		// Anonymize if poll's anonymize setting is true
		if ((bool)$this->anonymized) {
			return self::ANON_FULL;
		}

		// Anonymize if votes are hidden
		if ($this->getPollShowResults() === Poll::SHOW_RESULTS_NEVER
			|| ($this->getPollShowResults() === Poll::SHOW_RESULTS_CLOSED && (
				!$this->getPollExpire() || $this->getPollExpire() > time()
			))
		) {
			return self::ANON_FULL;
		}
		
		return self::ANON_PRIVACY;
	}

	public function getUser(): UserBase {
		/** @var UserMapper */
		$userMapper = (Container::queryClass(UserMapper::class));
		$user = $userMapper->getParticipant($this->getUserId(), $this->getPollId());
		$user->setAnonymizeLevel($this->getAnonymizeLevel());
		return $user;
	}
}
