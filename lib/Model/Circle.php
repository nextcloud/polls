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

use OCP\App\IAppManager;
use OCA\Circles\Api\v1\Circles;

use OCA\Polls\Exceptions\CirclesNotEnabledException;

class Circle extends UserGroupClass {
	public const TYPE = 'circle';
	public const ICON = 'icon-circles';

	private $circle;

	public function __construct(
		$id
	) {
		parent::__construct($id, self::TYPE);
		if (self::isEnabled()) {
			$this->icon = self::ICON;
			$this->circle = Circles::detailsCircle($id);
			$this->displayName = $this->circle->getName();
			$this->description = $this->circle->gettypeLongString();
		} else {
			throw new CirclesNotEnabledException();
		}
	}

	public static function isEnabled(): bool {
		return self::getContainer()->query(IAppManager::class)->isEnabledForUser('circles');
	}

	/**
	 * @return Circle[]
	 */
	public static function search(string $query = '', $skip = []): array {
		$circles = [];
		if (self::isEnabled()) {
			foreach (Circles::listCircles(\OCA\Circles\Model\Circle::CIRCLES_ALL, $query) as $circle) {
				if (!in_array($circle->getUniqueId(), $skip)) {
					$circles[] = new self($circle->getUniqueId());
				}
			}
		}

		return $circles;
	}

	/**
	 * @return User[]|Email[]|Contact[]
	 */
	public function getMembers() {
		$members = [];
		if (self::isEnabled()) {
			foreach (Circles::detailsCircle($this->id)->getMembers() as $circleMember) {
				if ($circleMember->getType() === Circles::TYPE_USER) {
					$members[] = new User($circleMember->getUserId());
				} elseif ($circleMember->getType() === Circles::TYPE_MAIL) {
					$members[] = new Email($circleMember->getUserId());
				} elseif ($circleMember->getType() === Circles::TYPE_CONTACT) {
					$members[] = new Contact($circleMember->getUserId());
				} else {
					continue;
				}
			}
		}
		return $members;
	}
}
