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

namespace OCA\Polls\Db;

use JsonSerializable;

use OCP\IUser;
use OCP\AppFramework\Db\Entity;
use OCA\Polls\Model\User;

/**
 * @method string getId()
 * @method void setId(integer $value)
 * @method string getToken()
 * @method void setToken(string $value)
 * @method string getType()
 * @method void setType(string $value)
 * @method int getPollId()
 * @method void setPollId(integer $value)
 * @method string getUserId()
 * @method void setUserId(string $value)
 * @method string getUserEmail()
 * @method void setUserEmail(string $value)
 * @method int getInvitationSent()
 * @method void setInvitationSent(integer $value)
 */
class Share extends Entity implements JsonSerializable {

	const TYPE_USER = User::TYPE_USER;
	const TYPE_EMAIL = User::TYPE_EMAIL;
	const TYPE_CIRCLE = User::TYPE_CIRCLE;
	const TYPE_GROUP = User::TYPE_GROUP;
	const TYPE_CONTACTGROUP = User::TYPE_CONTACTGROUP;
	const TYPE_CONTACT = User::TYPE_CONTACT;
	const TYPE_PUBLIC = 'public';
	const TYPE_EXTERNAL = 'external';

	/** @var string $token */
	protected $token;

	/** @var string $type */
	protected $type;

	/** @var int $pollId */
	protected $pollId;

	/** @var string $userId */
	protected $userId;

	/** @var string $userEmail */
	protected $userEmail;

	/** @var string $invitationSent */
	protected $invitationSent;

	/** @var string $displayName */
	protected $displayName;

	public function jsonSerialize() {
		return [
			'id' => intval($this->id),
			'token' => $this->token,
			'type' => $this->type,
			'pollId' => intval($this->pollId),
			'userId' => $this->userId,
			'userEmail' => $this->userEmail,
			'invitationSent' => intval($this->invitationSent),
			'displayName' => $this->getDisplayName(),
			'externalUser' => $this->externalUser()
		];
	}

	private function getDisplayName() {
		if ($this->type === self::TYPE_EMAIL && !$this->userId) {
			$user = new User($this->type, $this->userEmail, $this->userEmail, $this->displayName);
		} elseif ($this->type === self::TYPE_CONTACT && !$this->userId) {
			$user = new User($this->type, $this->userId, $this->userEmail, $this->displayName);
		} else {
			$user = new User($this->type, $this->userId);
		}
		return $user->getDisplayName();
	}

	private function externalUser() {
		return (!\OC::$server->getUserManager()->get($this->userId) instanceof IUser);
	}
}
