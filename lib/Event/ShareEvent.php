<?php
/*
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Share;

abstract class ShareEvent extends BaseEvent {
	public const ADD = 'share_add';
	public const CHANGE_EMAIL = 'share_change_email';
	public const CHANGE_TYPE = 'share_change_type';
	public const CHANGE_REG_CONSTR = 'share_change_reg_const';
	public const REGISTRATION = 'share_registration';
	public const DELETE = 'share_delete';

	/** @var Share */
	private $share;

	public function __construct(Share $share) {
		parent::__construct($share);
		$this->activityObject = 'poll';
		$this->share = $share;
	}

	public function getShare(): Share {
		return $this->share;
	}
}
