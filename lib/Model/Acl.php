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
use OCP\AppFramework\Db\DoesNotExistException;
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCP\IUserManager;
use OCP\IGroupManager;
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
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param PollMapper $pollMapper
	 * @param VoteMapper $voteMapper
	 * @param ShareMapper $shareMapper
	 *
	 */
	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		PollMapper $pollMapper,
		VoteMapper $voteMapper,
		ShareMapper $shareMapper
	) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->pollMapper = $pollMapper;
		$this->voteMapper = $voteMapper;
		$this->shareMapper = $shareMapper;
		$this->poll = new Poll;
		$this->share = new Share;
	}

	/**
	 * @NoAdminRequired
	 * @return self
	 * @throws NotAuthorizedException
	 */
	public function set($pollId = 0, $token = ''): Acl {
		try {
			$this->share = $this->shareMapper->findByToken($token);

			if (($this->getLoggedIn() && !$this->share->getValidAuthenticated())
			   || (!$this->getLoggedIn() && !$this->share->getValidPublic())
			) {
				throw new NotAuthorizedException;
			}

			$pollId = $this->share->getPollId();
		} catch (DoesNotExistException $e) {
			if (!$this->getLoggedIn()) {
				// Token is invalid and user is not logged in. Reject
				throw new NotAuthorizedException;
			}
		}

		try {
			$this->poll = $this->pollMapper->find($pollId);
		} catch (DoesNotExistException $e) {
			throw new NotAuthorizedException;
		}

		return $this;
	}

	/**
	 * @NoAdminRequired
	 * @return string
	 */
	public function getUserId() {
		if ($this->getLoggedIn()) {
			return \OC::$server->getUserSession()->getUser()->getUID();
		} else {
			return $this->share->getUserId();
		}
	}

	/**
	 * @NoAdminRequired
	 * @return string
	 */
	public function getDisplayName() {
		if ($this->getLoggedIn()) {
			return $this->userManager->get($this->getUserId())->getDisplayName();
		} else {
			return $this->share->getDisplayName();
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
		return $this->poll->getId();
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getIsOwner(): bool {
		return ($this->getLoggedIn() && $this->poll->getOwner() === $this->getUserId());
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getIsAdmin(): bool {
		return ($this->getLoggedIn() && $this->groupManager->isAdmin($this->getUserId()) && $this->poll->getAdminAccess());
	}

	/**
	 * @NoAdminRequired
	 * @return bool
	 */
	public function getAllowView(): bool {
		return (
			   $this->getAllowEdit()
			|| !$this->poll->getDeleted() && (
				   $this->getUserHasVoted()
				|| $this->getGroupShare()
				|| $this->getPersonalShare()
				|| $this->getPublicShare()
				|| ($this->poll->getAccess() === Poll::ACCESS_PUBLIC)
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
		) > 0;
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
			&& $this->getUserId();
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
		return !$this->poll->getDeleted() && boolval($this->getUserID());
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
		return $this->poll->getShowResults() === Poll::SHOW_RESULTS_ALWAYS
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
		return strval($this->share->getToken());
	}

	private function hasEmail():bool {
		if ($this->share->getToken()) {
			return strlen($this->share->getEmailAddress()) > 0;
		} else {
			return $this->getLoggedIn();
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
