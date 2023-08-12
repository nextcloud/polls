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

use OCA\Polls\Db\Option;

abstract class OptionEvent extends BaseEvent {
	public const ADD = 'option_add';
	public const UPDATE = 'option_update';
	public const CONFIRM = 'option_confirm';
	public const UNCONFIRM = 'option_unconfirm';
	public const DELETE = 'option_delete';

	public function __construct(
		protected Option $option,
	) {
		parent::__construct($option);
		$this->activityObjectType = 'poll';
		$this->activitySubjectParams['optionTitle'] = [
			'type' => 'highlight',
			'id' => $this->option->getId(),
			'name' => $this->option->getPollOptionText(),
		];
	}

	public function getOption(): Option {
		return $this->option;
	}
}
