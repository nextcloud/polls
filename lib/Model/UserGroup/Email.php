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


namespace OCA\Polls\Model\UserGroup;

use OCA\Polls\Helper\Container;

class Email extends UserBase {
	public const TYPE = 'email';
	public const ICON = 'icon-mail';

	public function __construct(
		string $id,
		string $displayName = '',
		string $emailAddress = ''
	) {
		parent::__construct($id, self::TYPE);
		$this->icon = self::ICON;
		$this->description = $emailAddress ? $emailAddress : Container::getL10N()->t('External Email');
		$this->richObjectType = 'email';
		
		$this->emailAddress = $emailAddress ? $emailAddress : $id;
		$this->displayName = $displayName ? $displayName : $this->displayName;
	}

	public function getDisplayName(): string {
		return $this->displayName ? $this->displayName : $this->id;
	}
}
