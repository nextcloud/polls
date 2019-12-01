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

use JsonSerializable;
use Exception;
use OCP\AppFramework\Db\DoesNotExistException;

use OCP\ILogger;
use OCP\IGroupManager;
use OCA\Polls\Db\Event;
use OCA\Polls\Db\EventMapper;
use OCA\Polls\Db\ShareMapper;

/**
 * Class Acl
 *
 * @package OCA\Polls\Model\Acl
 */
class Acl implements JsonSerializable {

	/** @var int */
	private $pollId = 0;

	/** @var string */
	private $token = '';

	/** @var bool */
	private $foundByToken = false;

	/** @var Event */
	// private $event;


	/**
	 * Acl constructor.
	 * @param $pollIdOrToken
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param ShareMapper $shareMapper
	 * @param EventMapper $eventMapper
	 * @param Event $eventMapper
	 *
	 */
	public function __construct(
		string $appName,
		$userId,
		ILogger $logger,
		IGroupManager $groupManager,
		EventMapper $eventMapper,
		ShareMapper $shareMapper,
		Event $event
	) {
		$this->userId = $userId;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->eventMapper = $eventMapper;
		$this->shareMapper = $shareMapper;
		$this->event = $event;
	}


	/**
	 * @return string
	 */
	 public function getUserId() {
		return $this->userId;
	}

	/**
	 * @return string
	 */
	public function setUserId($userId): Acl {
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPollId(): int {
		return $this->pollId;
	}

	/**
	 * @return int
	 */
	public function setPollId(int $pollId): Acl {
		$this->pollId = $pollId;
		$this->event = $this->eventMapper->find($this->pollId);

		return $this;
	}

	/**
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * @return string
	 */
	public function setToken(string $token): Acl {
		try {

			$this->token = $token;
			$share = $this->shareMapper->findByToken($token);
			$this->foundByToken = true;
			$this->setPollId($share->getPollId());

			if ($share->getType() === 'public') {
				$this->setUserId($this->userId);
			} else if ($share->getType() === 'group' && !\OC::$server->getUserSession()->isLoggedIn() ) {
				$this->logger->warning('unauthorized user accessed group share');
				throw new DoesNotExistException('unauthorizes access');
			}

		} catch (DoesNotExistException $e) {
			$this->setPollId(0);
			$this->setUserId(null);
			$this->token = '';
			$this->foundByToken = false;
		}
		return $this;
	}

	/**
	 * @return bool
	 */
	public function getIsOwner(): bool {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			return ($this->event->getOwner() === $this->userId);
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function getIsAdmin(): bool {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			return $this->groupManager->isAdmin($this->userId);
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function getAllowView(): bool {
		if ($this->pollId) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function getAllowVote(): bool {
		if ($this->pollId) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function getAllowComment(): bool {
		return $this->getAllowVote();
	}

	/**
	 * @return bool
	 */
	public function getAllowEdit(): bool {
		return ($this->getIsOwner() || $this->getIsAdmin());
	}

	/**
	 * @return bool
	 */
	public function getAllowSeeUsernames(): bool {
		return !(($this->event->getIsAnonymous() && !$this->getIsOwner()) || $this->event->getFullAnonymous());;
	}

	/**
	 * @return bool
	 */
	public function getAllowSeeAllVotes(): bool {
		// TODO: preparation for polls without displaying other votes
		if ($this->pollId) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function getFoundByToken(): bool {
		return $this->foundByToken;
	}

	/**
	* @return string
	*/
	public function getAccessLevel(): string {
		if ($this->getIsOwner()) {
			return 'owner';
		} elseif ($this->event->getAccess() === 'public') {
			return 'public';
		} elseif ($this->event->getAccess() === 'registered' && \OC::$server->getUserSession()->getUser()->getUID() === $this->userId) {
			return 'registered';
		} elseif ($this->event->getAccess() === 'hidden' && $this->getisOwner()) {
			return 'hidden';
		} elseif ($this->getIsAdmin()) {
			return 'admin';
		} else {
			return 'none';
		}
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return	[
			'userId'            => $this->getUserId(),
			'pollId'            => $this->getPollId(),
			'token'             => $this->getToken(),
			'isOwner'           => $this->getIsOwner(),
			'isAdmin'           => $this->getIsAdmin(),
			'allowView'         => $this->getAllowView(),
			'allowVote'         => $this->getAllowVote(),
			'allowComment'      => $this->getAllowComment(),
			'allowEdit'         => $this->getAllowEdit(),
			'allowSeeUsernames' => $this->getAllowSeeUsernames(),
			'allowSeeAllVotes'  => $this->getAllowSeeAllVotes(),
			'foundByToken'      => $this->getFoundByToken(),
			'accessLevel'       => $this->getAccessLevel()
		];
	}
}
