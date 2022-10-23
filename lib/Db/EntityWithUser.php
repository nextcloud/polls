<?php
/**
 * @copyright Copyright (c) 2017 Kai Schröer <git@schroeer.co>
 *
 * @author Kai Schröer <git@schroeer.co>
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

use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Helper\Container;
use OCP\AppFramework\Db\Entity;
use OCP\IUser;
use OCP\IUserManager;

/**
 * @method string getUserId()
 * @method int getPollId()
 */

abstract class EntityWithUser extends Entity {
	/** @var string $publicUserId */
	protected $publicUserId = '';

	public function getIsNoUser(): bool {
		return !(Container::queryClass(IUserManager::class)->get($this->getUserId()) instanceof IUser);
	}

	public function getDisplayName(): string {
		if (!$this->getUserId()) {
			return '';
		}
		if ($this->getIsNoUser()) {
			// get displayName from share
			try {
				$share = Container::queryClass(ShareMapper::class)->findByPollAndUser($this->getPollId(), $this->getUserId());
			} catch (ShareNotFoundException $e) {
				// use fake share
				$share = $e->getReplacement();
			}
			return $share->getDisplayName();
		}

		return Container::queryClass(IUserManager::class)->get($this->getUserId())->getDisplayName();
	}

	private function getPublicUserId(): string {
		if (!$this->getUserId()) {
			return '';
		}

		if ($this->publicUserId) {
			return $this->publicUserId;
		}

		return $this->getUserId();
	}

	public function generateHashedUserId(): void {
		$this->publicUserId = hash('md5', $this->getUserId());
	}

	public function getUser(): array {
		return [
			'userId' => $this->getPublicUserId(),
			'displayName' => $this->getDisplayName(),
			'isNoUser' => $this->getIsNoUser(),
		];
	}
}
