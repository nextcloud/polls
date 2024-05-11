<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 René Gieling <github@dartcafe.de>
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

namespace OCA\Polls;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Model\UserBase;
use OCP\ISession;
use OCP\IUserSession;

class UserSession {
	/** @var string */
	public const SESSION_KEY_USER_ID = 'ncPollsUserId';
	/** @var string */
	public const SESSION_KEY_SHARE_TOKEN = 'ncPollsPublicToken';
	/** @var string */
	public const SESSION_KEY_SHARE_TYPE = 'ncPollsShareType';
	/** @var string */
	public const CLIENT_ID = 'ncPollsClientId';
	/** @var string */
	public const CLIENT_TZ = 'ncPollsClientTimeZone';

	public const TABLE = Share::TABLE;
	protected ?UserBase $currentUser = null;
	protected ?Share $share = null;

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		protected ISession $session,
		protected IUserSession $userSession,
		protected UserMapper $userMapper,
		protected ShareMapper $shareMapper,
	) {
	}

	/**
	 * Get current user
	 *
	 * Returns a UserBase child for the current (share|nextcloud) user based on
	 * - the logged in user or
	 * - the stored session share token
	 *
	 */
	public function getUser(): UserBase {
		if (!$this->currentUser) {

			if ($this->getIsLoggedIn()) {
				$this->currentUser = $this->userMapper->getUserFromUserBase($this->userSession->getUser()->getUID());
			} else {
				$this->currentUser = $this->userMapper->getUserFromShareToken($this->getShareToken());
			}
		}

		return $this->currentUser;
	}

	public function getCurrentUserId(): string {
		if (!$this->session->get(self::SESSION_KEY_USER_ID)) {
			$this->session->set(self::SESSION_KEY_USER_ID, $this->getUser()->getId());
		}

		return (string) $this->session->get(self::SESSION_KEY_USER_ID);
	}

	public function getIsLoggedIn(): bool {
		return $this->userSession->isLoggedIn();
	}

	public function cleanSession(): void {
		$this->session->remove(self::SESSION_KEY_SHARE_TOKEN);
		$this->session->remove(self::SESSION_KEY_SHARE_TYPE);
		$this->session->remove(self::SESSION_KEY_USER_ID);
		$this->share = null;
		$this->currentUser = null;
	}

	/**
	 * Set share token in case user accesses via a share token
	 *
	 * @param string $token
	 */
	public function setShareToken(string $token): void {
		if ($this->getShareToken() !== $token) {
			// invalidate session if token changes
			// $this->cleanSession();
		}
		$this->session->set(self::SESSION_KEY_SHARE_TOKEN, $token);
	}

	/**
	 * Get share token
	 *
	 * Returns the stored session share token
	 *
	 * @return string
	 */
	public function getShareToken(): string {
		return (string) $this->session->get(self::SESSION_KEY_SHARE_TOKEN);
	}

	/**
	 * Has share
	 *
	 * Returns true if a share token is stored in the session
	 *
	 * @return bool
	 */
	public function hasShare(): bool {
		return (bool) $this->getShareToken();
	}

	/**
	 * Get share
	 *
	 * Returns a Share object based on the stored session share token
	 *
	 * @return Share
	 */
	public function getShare(): ?Share {
		if ($this->hasShare() && !$this->share) {
			$this->share = $this->shareMapper->findByToken($this->getShareToken());
		}
		return $this->share;
	}

	/**
	 * Get share type
	 *
	 * Returns the stored session share type
	 *
	 * @return string
	 */
	public function getShareType(): string {
		if (!$this->hasShare()) {
			return '';
		}

		if (!$this->session->get(self::SESSION_KEY_SHARE_TYPE)) {
			$this->session->set(self::SESSION_KEY_SHARE_TYPE, $this->getShare()->getType());
		}

		return (string) $this->session->get(self::SESSION_KEY_SHARE_TYPE);
	}

	public function getClientId(): string {
		return (string) $this->session->get(self::CLIENT_ID);
	}

	public function getClientIdHashed(): string {
		return hash('md5', $this->getClientId());
	}


	public function setClientId(string $clientId): void {
		$this->session->set(self::CLIENT_ID, $clientId);
	}

	public function getClientTimeZone(): string {
		return (string) $this->session->get(self::CLIENT_TZ);
	}

	public function setClientTimeZone(string $clientTimeZone): void {
		$this->session->set(self::CLIENT_TZ, $clientTimeZone);
	}
}
