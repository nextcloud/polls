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


namespace OCA\Polls\Model;

use OCP\IUserManager;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\Share\IShare;

class User extends UserGroupClass {
	public const TYPE = 'user';
	public const ICON = 'icon-user';

	private $user;

	public function __construct(
		$id
	) {
		parent::__construct($id, self::TYPE);
		$this->icon = self::ICON;
		$this->isNoUser = false;
		$this->description = \OC::$server->getL10N('polls')->t('User');

		$this->user = \OC::$server->getUserManager()->get($this->id);
		$this->displayName = $this->user->getDisplayName();
		$this->emailAddress = $this->user->getEMailAddress();
		$this->language = \OC::$server->getConfig()->getUserValue($this->id, 'core', 'lang');
	}

	public function getUserIsDisabled(): bool {
		return !\OC::$server->getUserManager()->get($this->id)->isEnabled();
	}

	public static function listRaw(string $query = '', array $types = [], bool $ISearchToggle = false): array {
		$c = self::getContainer();

		if ($ISearchToggle) {
			$users = [];
			list($result, $more) = $c->query(ISearch::class)->search($query, $types, true, 200, 0);
			return $result;
		} else {
			return $c->query(IUserManager::class)
					 ->search($query);
			return \OC::$server->getUserManager()->search($query);
		}
	}

	/**
	 * @return User[]
	 *
	 * @psalm-return list<User>
	 */
	public static function search(string $query = '', array $skip = []): array {
		$users = [];
		// $types = [IShare::TYPE_USER];
		$types = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_EMAIL,
			IShare::TYPE_CIRCLE,
			IShare::TYPE_DECK
		];
		$ISearchToggle = true;


		if ($ISearchToggle) {
			$result = self::listRaw($query, $types, $ISearchToggle);
			\OC::$server->getLogger()->alert(json_encode($result));
			foreach ($result['users'] as $user) {
				if (!in_array($user['value']['shareWith'], $skip)) {
					$users[] = new Self($user['value']['shareWith']);
				}
			}
			foreach ($result['exact']['users'] as $user) {
				if (!in_array($user['value']['shareWith'], $skip)) {
					$users[] = new Self($user['value']['shareWith']);
				}
			}
		} else {
			$result = self::listRaw($query, $types, $ISearchToggle);
			foreach ($result as $user) {
				if (!in_array($user->getUID(), $skip)) {
					$users[] = new Self($user->getUID());
				}
			}
		}
		return $users;
	}
}
