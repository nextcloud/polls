<?php
/**
 * @copyright Copyright (c) 2020 René Gieling <github@dartcafe.de>
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

namespace OCA\Polls\Exceptions;

use OCA\Polls\Db\Share;
use OCA\Polls\Model\UserBase;

class ShareNotFoundException extends NotFoundException {
	/** @var int $pollId */
	protected $pollId = 0;

	/** @var string $userId */
	protected $userId = '';

	/** @var string $token */
	protected $token = '';

	public function __construct(
		string $e = 'Share not found',
		string $userId = '',
		int $pollId = 0,
		string $token = ''
	) {
		parent::__construct($e);
		$this->userId = $userId;
		$this->pollId = $pollId;
		$this->token = $token;
	}

	/**
	 * Returns a fake share in case of deleted shares
	 */
	public function getReplacement(): ?Share {
		if (!$this->userId) {
			return null;
		}

		$share = new Share;
		$share->setUserId($this->userId);
		$share->setPollId($this->pollId);
		$share->setType(UserBase::TYPE_EXTERNAL);
		$share->setToken('deleted_share_' . $this->userId . '_' . $this->pollId);
		$share->setDisplayName('Deleted User');
		return $share;
	}
}
