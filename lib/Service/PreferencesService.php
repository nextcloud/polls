<?php
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

use OCA\Polls\Exceptions\NotAuthorizedException;
use OCP\AppFramework\Db\DoesNotExistException;

use OCA\Polls\Db\Preferences;
use OCA\Polls\Db\PreferencesMapper;

class PreferencesService {

	/** @var PreferencesMapper */
	private $preferencesMapper;

	/** @var Preferences */
	private $preferences;

	/** @var String */
	private $userId;

	public function __construct(
		$UserId,
		PreferencesMapper $preferencesMapper
	) {
		$this->userId = $UserId;
		$this->preferencesMapper = $preferencesMapper;
		try {
			$this->preferences = $this->preferencesMapper->find($this->userId);
		} catch (DoesNotExistException $e) {
			if ($UserId) {
				$this->preferences = new Preferences();
				$this->preferences->setUserId($this->userId);
				$this->preferences->setPreferences('');
				$this->preferences = $this->preferencesMapper->insert($this->preferences);
			} else {
				throw new NotAuthorizedException;
			}
		}
	}
	/**
	 * 	 * Read all preferences
	 *
	 * @return Preferences
	 */
	public function get(): Preferences {
		return $this->preferences;
	}

	/**
	 * 	 * Write references
	 *
	 * @return Preferences
	 */
	public function write(string $settings = ''): Preferences {
		if (!$this->userId) {
			throw new NotAuthorizedException;
		}

		$this->preferences->setPreferences(json_encode($settings));
		$this->preferences->setTimestamp(time());
		return $this->preferencesMapper->update($this->preferences);
	}
}
