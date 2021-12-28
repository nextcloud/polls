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

namespace OCA\Polls\Settings;

use OCP\IL10N;
use OCP\Activity\ISetting;

class ActivitySettings implements ISetting {

	/** @var IL10N */
	protected $l;

	/**
	 * @param IL10N $l
	 */
	public function __construct(IL10N $l) {
		$this->l = $l;
	}

	public function getIdentifier() : string {
		return 'poll';
	}

	public function getName() : string {
		return $this->l->t('Events happening inside of a <strong>poll</strong>');
	}

	public function getPriority() : int {
		return 90;
	}

	public function canChangeStream() : bool {
		return true;
	}

	public function isDefaultEnabledStream() : bool {
		return true;
	}

	public function canChangeMail()  : bool {
		return true;
	}

	public function isDefaultEnabledMail() : bool {
		return false;
	}
}
