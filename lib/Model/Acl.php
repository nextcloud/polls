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
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCP\IUserManager;
use OCP\IGroupManager;
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

	/** @var string */
	private $token = '';

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

	/** @var Share */
	private $share;

	/**
	 * Acl constructor.
	 * @param string $UserId
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param PollMapper $pollMapper
	 * @param VoteMapper $voteMapper
	 * @param ShareMapper $shareMapper
	 * @param Poll $poll
	 * @param Share $share
	 *
	 */
	public function __construct(
		$UserId,
		IUserManager $userManager,
		IGroupManager $groupManager,
		PollMapper $pollMapper,
		VoteMapper $voteMapper,
		ShareMapper $shareMapper,
		Poll $poll,
		Share $share
	) {
		$this->userId = $UserId;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->pollMapper = $pollMapper;
		$this->voteMapper = $voteMapper;
		$this->shareMapper = $shareMapper;
		$this->poll = $poll;
		$this->share = $share;
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function set($pollId = 0, $token = ''): Acl {
		if ($token) {
			\OC::$server->getLogger()->debug('Share token: ' . $token);

			$this->token = $token;
			$this->pollId = 0;
			$this->userId = null;
			$this->share = $this->shareMapper->findByToken($token);

			if (\OC::$server->getUserSession()->isLoggedIn()) {
				if ($this->share->getType() !== Share::TYPE_GROUP
					&& $this->share->getType() !== Share::TYPE_PUBLIC) {
					throw new NotAuthorizedException;
				}

				$this->userId = \OC::$server->getUserSession()->getUser()->getUID();
			} else {
				if ($this->share->getType() === Share::TYPE_GROUP
					|| $this->share->getType() === Share::TYPE_USER) {
					throw new NotAuthorizedException;
				}

				$this->userId = $this->share->getUserId();
			}

			$this->pollId = $this->share->getPollId();
		} elseif ($pollId) {
			$this->userId = \OC::$server->getUserSession()->getUser()->getUID();
			$this->pollId = $pollId;
			$this->share = null;
		}

		$this->poll = $this->pollMapper->find($this->pollId);

		return $this;
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
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
				if ($item->getType() === Share::TYPE_GROUP && $this->groupManager->isInGroup($this->getUserId(), $item->getUserId())) {
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
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
				if (
					($item->getType() === Share::TYPE_USER
						|| $item->getType() === Share::TYPE_EXTERNAL
						|| $item->getType() === Share::TYPE_EMAIL
						|| $item->getType() === Share::TYPE_CONTACT
					)
					&& $item->getUserId() === $this->getUserId()
				) {
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
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
				if ($item->getType() === Share::TYPE_PUBLIC && $item->getToken() === $this->getToken()) {
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
		return ($this->getAllowView() || $this->getToken())
			&& !$this->getExpired()
			&& !$this->poll->getDeleted()
			&& $this->userId;
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getAllowSubscribe(): bool {
		return ($this->hasEmail())
			&& !$this->poll->getDeleted()
			&& $this->getAllowView();
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
	public function getAllowSeeResults(): bool {
		return $this->poll->getShowResults() === 'always'
			|| ($this->poll->getShowResults() === 'expired' && $this->getExpired())
			|| $this->getIsOwner();
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getAllowSeeUsernames(): bool {
		return !$this->poll->getAnonymous() || $this->getIsOwner();
	}

	/**
	 * @NoAdminRequired
	 * @return string
	 */
	public function getToken(): string {
		return $this->token;
	}

	private function hasEmail():bool {
		if ($this->share) {
			return strlen($this->share->getUserEmail()) > 0;
		} else {
			return \OC::$server->getUserSession()->isLoggedIn();
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
			'allowSeeResults'   => $this->getAllowSeeResults(),
			'allowSeeUsernames' => $this->getAllowSeeUsernames(),
			'allowSubscribe'    => $this->getAllowSubscribe(),
			'userHasVoted'		=> $this->getUserHasVoted(),
			'groupShare'        => $this->getGroupShare(),
			'personalShare'     => $this->getPersonalShare(),
			'publicShare'     	=> $this->getPublicShare()
		];
	}
}
