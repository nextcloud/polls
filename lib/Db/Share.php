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

use OCP\AppFramework\Db\Entity;
use OCA\Polls\Model\UserGroupClass;

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
 * @method string getEmailAddress()
 * @method void setEmailAddress(string $value)
 * @method int getInvitationSent()
 * @method void setInvitationSent(integer $value)
 * @method int getDisplayName()
 * @method void setDisplayName(string $value)
 */
class Share extends Entity implements JsonSerializable {

	// Only authenticated access
	public const TYPE_USER = 'user';
	public const TYPE_GROUP = 'group';

	// Public and authenticated Access
	public const TYPE_PUBLIC = 'public';

	// Only public access
	public const TYPE_EMAIL = 'email';
	public const TYPE_CONTACT = 'contact';
	public const TYPE_EXTERNAL = 'external';

	// no direct Access
	public const TYPE_CIRCLE = 'circle';
	public const TYPE_CONTACTGROUP = 'contactGroup';

	/** @var string $token */
	protected $token;

	/** @var string $type */
	protected $type;

	/** @var int $pollId */
	protected $pollId;

	/** @var string $userId */
	protected $userId;

	/** @var string $emailAddress */
	protected $emailAddress;

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
			'userId' => $this->getUserId(),
			'emailAddress' => $this->emailAddress,
			'invitationSent' => intval($this->invitationSent),
			'displayName' => $this->displayName,
			'isNoUser' => !($this->type === self::TYPE_USER),
			'validPublic' => $this->getValidPublic(),
			'validAuthenticated' => $this->getValidAuthenticated(),
			'URL' => $this->getURL()
		];
	}

	public function getURL() {
		if ($this->type === self::TYPE_USER || $this->type === self::TYPE_GROUP) {
			return \OC::$server->getUrlGenerator()->linkToRouteAbsolute(
				'polls.page.vote',
				['id' => $this->pollId]
			);
		} else {
			return \OC::$server->getUrlGenerator()->linkToRouteAbsolute(
				'polls.page.vote_publicpublic',
				['token' => $this->token]
			);
		}
	}
	public function getUserId() {
		if ($this->type === self::TYPE_CONTACTGROUP) {
			// contactsgroup had the prefix contactgroup_ until version 1.5
			// strip it out
			$parts = explode("contactgroup_", $this->userId);
			$userId = end($parts);
			return $userId;
		}
		return $this->userId;
	}

	/**
	 * @return UserGroupClass
	 */
	public function getUserObject() {
		return UserGroupClass::getUserGroupChild(
			$this->type,
			$this->userId,
			$this->displayName,
			$this->emailAddress
		);
	}

	/**
	 * @return UserGroupClass[]
	 */
	public function getMembers() {
		if ($this->type === self::TYPE_GROUP
		|| $this->type === self::TYPE_CONTACTGROUP
		|| $this->type === self::TYPE_CIRCLE) {
			$group = UserGroupClass::getUserGroupChild($this->type, $this->getUserId());
			return $group->getMembers();
		} else {
			return [UserGroupClass::getUserGroupChild(
				$this->type,
				$this->userId,
				$this->displayName,
				$this->emailAddress
			)];
		}
	}

	/**
	 * @return bool
	 */
	public function getValidPublic() {
		return (
			   $this->type === self::TYPE_PUBLIC
			|| $this->type === self::TYPE_EMAIL
			|| $this->type === self::TYPE_CONTACT
			|| $this->type === self::TYPE_EXTERNAL);
	}

	/**
	 * @return bool
	 */
	public function getValidAuthenticated() {
		return (
			   $this->type === self::TYPE_PUBLIC
			|| $this->type === self::TYPE_USER
			|| $this->type === self::TYPE_GROUP);
	}
}
