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

use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUser;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Db\ShareMapper;

/**
 * Class Acl
 *
 * @package OCA\Polls\Model\Acl
 */
class Acl implements JsonSerializable {

	/** @var int */
	private $pollId = 0;

	/** @var ILogger */
	private $logger;

	/** @var array */
	private $shares = [];

	/** @var string */
	private $token = '';

	/** @var bool */
	private $foundByToken = false;

	/** @var string */
	private $userId;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var PollMapper */
	private $pollMapper;

	/** @var VoteMapper */
	private $voteMapper;

	/** @var ShareMapper */
	private $shareMapper;

	/** @var Poll */
	private $poll;


	/**
	 * Acl constructor.
	 * @param string $appName
	 * @param string $userId
	 * @param ILogger $logger
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param PollMapper $pollMapper
	 * @param VoteMapper $voteMapper
	 * @param ShareMapper $shareMapper
	 * @param Poll $pollMapper
	 *
	 */
	public function __construct(
		$userId,
		ILogger $logger,
		IUserManager $userManager,
		IGroupManager $groupManager,
		PollMapper $pollMapper,
		VoteMapper $voteMapper,
		ShareMapper $shareMapper,
		Poll $poll
	) {
		$this->userId = $userId;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->pollMapper = $pollMapper;
		$this->voteMapper = $voteMapper;
		$this->shareMapper = $shareMapper;
		$this->poll = $poll;
	}


	/**
	 * @NoAdminRequired
	 * @return string
	 */
	 public function getUserId() {
		return $this->userId;
	}

	/**
	 * @NoAdminRequired
	 * @return string
	 */
	public function getDisplayName() {
		// $this->userManager = \OC::$server->getUserManager();

		if ($this->userManager->get($this->userId) instanceof IUser) {
			return $this->userManager->get($this->userId)->getDisplayName();
		} else {
			return $this->userId;
		}
	}

	/**
	 * @NoAdminRequired
	 * @return string
	 */
	public function setUserId($userId): Acl {
		$this->userId = $userId;
		return $this;
	}

	/**
	 * @NoAdminRequired
	 * @return string
	 */
	public function getLoggedIn() {
		return \OC::$server->getUserSession()->isLoggedIn();
	}

	/**
	 * @NoAdminRequired
	 * @return int
	 */
	public function getPollId(): int {
		return $this->pollId;
	}

	/**
	 * @NoAdminRequired
	 * @return int
	 */
	public function setPollId(int $pollId): Acl {
		$this->pollId = $pollId;
		$this->poll = $this->pollMapper->find($this->pollId);
		$this->shares = $this->shareMapper->findByPoll($this->pollId);

		return $this;
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getIsOwner(): bool {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			return ($this->poll->getOwner() === $this->userId);
		} else {
			return false;
		}
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getIsAdmin(): bool {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			return ($this->groupManager->isAdmin($this->userId) && $this->poll->getAdminAccess());
		} else {
			return false;
		}
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getAllowView(): bool {
		return (
			   $this->getIsOwner()
			|| ($this->getIsAdmin() && $this->poll->getAdminAccess())
			|| !$this->poll->getDeleted() && (
				   $this->getUserHasVoted()
				|| $this->getGroupShare()
				|| $this->getPersonalShare()
				|| $this->getPublicShare()
				|| ($this->poll->getAccess() !== 'hidden' && !$this->getPublicShare())
			)
		);
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getGroupShare(): bool {
		return count(
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function($item) {
				if ($item->getType() === 'group' && $this->groupManager->isInGroup($this->getUserId(), $item->getUserId())) {
					return true;
				}
			})
		);
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getUserHasVoted(): bool {
		return count(
			$this->voteMapper->findParticipantsVotes($this->getPollId(), $this->getUserId())
		);
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getPersonalShare(): bool {

		return count(
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function($item) {
				if (($item->getType() === 'user' || $item->getType() === 'external') && $item->getUserId() === $this->getUserId()) {
					return true;
				}
			})
		);
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getPublicShare(): bool {

		return count(
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function($item) {
				if ($item->getType() === 'public' && $item->getToken() === $this->getToken()) {
					return true;
				}
			})
		);
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getExpired(): bool {
		return (
			   $this->poll->getExpire() > 0
			&& $this->poll->getExpire() < time()
		);
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getAllowVote(): bool {
		if (
			   ($this->getAllowView() || $this->getFoundByToken())
			&& !$this->getExpired()
			&& !$this->poll->getDeleted()
			&& $this->userId

		) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getAllowComment(): bool {
		return !$this->poll->getDeleted() && boolval($this->userId);
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getAllowEdit(): bool {
		return ($this->getIsOwner() || $this->getIsAdmin());
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getAllowSeeUsernames(): bool {
		return !(($this->poll->getAnonymous() && !$this->getIsOwner()) || $this->poll->getFullAnonymous()); ;
	}

	/**
	 * @NoAdminRequired
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
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getFoundByToken(): bool {
		return $this->foundByToken;
	}

	/**
	 * @NoAdminRequired
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}

	/**
	 * @NoAdminRequired
	 * @return string
	 */
	public function setToken(string $token): Acl {
		try {

			$this->token = $token;
			$share = $this->shareMapper->findByToken($token);
			$this->foundByToken = true;
			$this->setPollId($share->getPollId());

			if (($share->getType() === 'group' || $share->getType() === 'user') && !\OC::$server->getUserSession()->isLoggedIn()) {
				// User must be logged in for shareType user and group
				$this->setPollId(0);
				$this->setUserId(null);
				$this->token = '';
				$this->foundByToken = false;
			} else if (($share->getType() === 'group' || $share->getType() === 'public') && \OC::$server->getUserSession()->isLoggedIn()) {
				// Use user name of authorized user shareType public and group if user is logged in
				$this->setUserId($this->userId);
			} else {
				$this->setUserId($share->getUserId());
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
	 * @NoAdminRequired
	 * @return string
	 */
	public function getAccessLevel(): string {
		if ($this->getIsOwner()) {
			return 'owner';
		} elseif ($this->poll->getAccess() === 'public') {
			return 'public';
		} elseif ($this->poll->getAccess() === 'registered' && \OC::$server->getUserSession()->getUser()->getUID() === $this->userId) {
			return 'registered';
		} elseif ($this->poll->getAccess() === 'hidden' && $this->getisOwner()) {
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
			'displayName'       => $this->getDisplayName(),
			'loggedIn'			=> $this->getLoggedIn(),
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
			'userHasVoted'		=> $this->getUserHasVoted(),
			'groupShare'        => $this->getGroupShare(),
			'personalShare'     => $this->getPersonalShare(),
			'publicShare'     	=> $this->getPublicShare(),
			'foundByToken'      => $this->getFoundByToken(),
			'accessLevel'       => $this->getAccessLevel()
		];
	}
}
