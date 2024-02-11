<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Db;

use OCA\Polls\Helper\Container;
use OCA\Polls\Model\UserBase;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method int getPollId()
 *
 * Joined Attributes
 * @method string getAnonymized()
 * @method int getPollOwnerId()
 * @method int getPollShowResults()
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
		$this->addType('anonymized', 'int');
		$this->addType('poll_expire', 'int');
	}
	/**
	 * Anonymized the user completely (ANON_FULL) or just strips out personal information
	 */
	public function getAnonymizeLevel(): string {
		$currentUserId = Container::queryClass(UserMapper::class)->getCurrentUser()->getId();
		// Don't censor for poll owner or it is the current user's entity
		if ($this->getPollOwnerId() === $currentUserId || $this->getUserId() === $currentUserId) {
			return self::ANON_NONE;
		}

		// Anonymize if poll's anonymize setting is true
		if ((bool) $this->anonymized) {
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
