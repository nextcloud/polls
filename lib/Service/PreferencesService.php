<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

namespace OCA\Polls\Service;

use OCA\Polls\Db\Preferences;
use OCA\Polls\Db\PreferencesMapper;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserSession;

class PreferencesService {
	private ?string $userId = null;
	
	public function __construct(
		private IUserSession $userSession,
		private PreferencesMapper $preferencesMapper,
		private Preferences $preferences,
	) {
		$this->userId = $this->userSession->getUser()?->getUID();
		$this->load();
	}

	public function load(?string $userId = null): Preferences {
		try {
			$this->preferences = $this->preferencesMapper->find($userId ?? $this->userId);
		} catch	(DoesNotExistException $e) {
			$this->preferences = new Preferences;
		}

		return $this->preferences;
	}

	public function get(): Preferences {
		return $this->preferences;
	}
	
	/**
	 * Write references
	 */
	public function write(array $preferences): Preferences {
		if (!$this->userId) {
			throw new NotAuthorizedException();
		}

		$this->preferences->setPreferences(json_encode($preferences));
		$this->preferences->setTimestamp(time());
		$this->preferences->setUserId($this->userId);
		
		if ($this->preferences->getId() > 0) {
			return $this->preferencesMapper->update($this->preferences);
		} else {
			return $this->preferencesMapper->insert($this->preferences);
			
		}

	}
}
