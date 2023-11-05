<?php
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Model\Group;

use OCA\Circles\Api\v1\Circles;
use OCA\Circles\Model\Circle as CirclesCircle;
use OCA\Polls\Exceptions\CirclesNotEnabledException;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\User\Contact;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\User;
use OCA\Polls\Model\UserBase;

class Circle extends UserBase {
	public const TYPE = 'circle';
	public const ICON = 'icon-circles';

	private CirclesCircle $circle;

	public function __construct(
		string $id
	) {
		parent::__construct($id, self::TYPE);
		$this->icon = self::ICON;
		$this->description = $this->l10n->t('Circle');
		$this->richObjectType = 'circle';

		if (self::isEnabled()) {
			$this->circle = Circles::detailsCircle($id);
			$this->displayName = $this->circle->getName();
		} else {
			throw new CirclesNotEnabledException();
		}
	}

	public static function isEnabled() : bool {
		return Container::isAppEnabled('circles');
	}

	public function getRichObjectString() : array {
		return [
			'type' => $this->richObjectType,
			'id' => $this->getId(),
			'name' => $this->getDisplayName(),
			'link' => $this->circle->getUrl(),
		];
	}

	/**
	 * @return Circle[]
	 */
	public static function search(string $query = '', array $skip = []) : array {
		$circles = [];
		if (self::isEnabled()) {
			foreach (Circles::listCircles(CirclesCircle::CIRCLES_ALL, $query) as $circle) {
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
	public function getMembers(): array {
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
