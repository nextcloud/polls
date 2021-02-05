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

class GenericUser extends UserGroupClass {
	public const TYPE = 'external';
	public const ICON_DEFAULT = 'icon-share';
	public const ICON_PUBLIC = 'icon-public';

	public function __construct(
		$id,
		$type = self::TYPE,
		$displayName = '',
		$emailAddress = ''
	) {
		parent::__construct($id, $type);
		$this->displayName = $displayName;
		$this->emailAddress = $emailAddress;

		if ($type === UserGroupClass::TYPE_PUBLIC) {
			$this->icon = self::ICON_PUBLIC;
			$this->description = \OC::$server->getL10N('polls')->t('Public link');
		} else {
			$this->icon = self::ICON_DEFAULT;
			$this->description = \OC::$server->getL10N('polls')->t('External user');
		}
	}
}
