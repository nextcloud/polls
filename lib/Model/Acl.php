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
use OCA\Polls\Exceptions\VoteLimitExceededException;

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
	public const PERMISSION_VIEW ='view';
	public const PERMISSION_EDIT ='edit';
	public const PERMISSION_DELETE ='delete';
	public const PERMISSION_COMMENT ='comment';
	public const PERMISSION_SUBSCRIBE ='subscribe';
	public const PERMISSION_VOTE ='vote';
	public const PERMISSION_SEE_RESULTS ='seeResults';
	public const PERMISSION_SEE_USERNAMES ='seeUserNames';
	public const PERMISSION_TAKE_OVER ='takeOver';

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
	 * load share via token and than call setShare
	 */
	public function setToken(string $token = ''): Acl {
		try {
			return $this->setShare($this->shareMapper->findByToken($token));
		} catch (DoesNotExistException $e) {
			throw new NotAuthorizedException('Error loading share ' . $token);
		}
	}

	/**
	 * setShare - sets and validates the share
	 * read access is
	 */
	public function setShare(Share $share): Acl {
		$this->share = $share;
		$this->validateShareAccess();
		$this->setPollId($share->getPollId());
		$this->request(Self::PERMISSION_VIEW);
		return $this;
	}

	public function getToken(): string {
		return strval($this->share->getToken());
	}

	public function setPollId(int $pollId = 0): Acl {
		try {
			return $this->setPoll($this->pollMapper->find($pollId));
		} catch (DoesNotExistException $e) {
			throw new NotAuthorizedException('Error loading poll ' . $pollId);
		}
	}

	public function setPoll(Poll $poll): Acl {
		$this->poll = $poll;
		return $this;
	}

	public function getPollId(): int {
		return $this->poll->getId();
	}

	public function getUserId() {
		if ($this->getLoggedIn()) {
			return \OC::$server->getUserSession()->getUser()->getUID();
		} else {
			return $this->share->getUserId();
		}
	}

	public function validateUserId(string $userId): void {
		if ($this->getUserId() !== $userId) {
			throw new NotAuthorizedException;
		}
	}

	private function getDisplayName(): string {
		if ($this->getLoggedIn()) {
			return $this->userManager->get($this->getUserId())->getDisplayName();
		} else {
			return $this->share->getDisplayName();
		}
	}

	public function isAllowed(string $permission): bool {
		switch ($permission) {
			case Self::PERMISSION_VIEW:
				if ($this->getIsOwner() || $this->hasAdminAccess()) {
					// always grant access, if user has edit rights
					return true;
				} elseif ($this->poll->getDeleted()) {
					// always deny access, if poll is deleted
					return false;
				} elseif ($this->poll->getAccess() === Poll::ACCESS_PUBLIC) {
					// grant access if poll poll is public
					return true;
				} elseif ($this->getUserIsInvolved()) {
					// grant access if user is involved in poll in any way
					return true;
				} elseif ($this->getToken()) {
					// user has token
					return true;
				}
				break;

			case Self::PERMISSION_EDIT:
				return $this->getIsOwner() || $this->hasAdminAccess();
			case Self::PERMISSION_DELETE:
				return $this->getIsOwner() || $this->hasAdminAccess() || $this->getIsAdmin();
			case Self::PERMISSION_COMMENT:
				return true;
			case Self::PERMISSION_SUBSCRIBE:
				return $this->hasEmail();
			case Self::PERMISSION_VOTE:
				return !$this->poll->getExpired() && $this->share->getType() !== Share::TYPE_PUBLIC;
			case Self::PERMISSION_SEE_RESULTS:
				if ($this->getIsOwner()) {
					return true;
				} elseif ($this->poll->getShowResults() === Poll::SHOW_RESULTS_ALWAYS) {
					return true;
				} elseif ($this->poll->getShowResults() === Poll::SHOW_RESULTS_CLOSED && $this->poll->getExpired()) {
					return true;
				}
				break;
			case Self::PERMISSION_SEE_USERNAMES:
				return $this->getIsOwner() || !$this->poll->getAnonymous();
			case Self::PERMISSION_TAKE_OVER:
				return $this->getIsAdmin();
			default:
				break;
		}
		return false;
	}

	public function request(string $permission): void {
		if (!$this->isAllowed($permission)) {
			throw new NotAuthorizedException('denied permission ' . $permission);
		}
	}

	public function getAllowYesVote(): bool {
		return !($this->poll->getVoteLimit() && $this->getYesVotes() >= $this->poll->getVoteLimit());
	}

	public function getOptionLimit(): int {
		return $this->poll->getOptionLimit();
	}

	private function getYesVotes(): int {
		return $this->voteMapper->countYesVotes($this->getUserId(), $this->getPollId());
	}

	public function requestYesVotes(): void {
		if (!$this->getAllowYesVote()) {
			throw new VoteLimitExceededException;
		}
	}

	public function jsonSerialize(): array {
		return	[
			'allowComment'      => $this->isAllowed(Self::PERMISSION_COMMENT),
			'allowEdit'         => $this->isAllowed(Self::PERMISSION_EDIT),
			'allowSeeResults'   => $this->isAllowed(Self::PERMISSION_SEE_RESULTS),
			'allowSeeUsernames' => $this->isAllowed(Self::PERMISSION_SEE_USERNAMES),
			'allowSubscribe'    => $this->isAllowed(Self::PERMISSION_SUBSCRIBE),
			'allowView'         => $this->isAllowed(Self::PERMISSION_VIEW),
			'allowVote'         => $this->isAllowed(Self::PERMISSION_VOTE),
			'displayName'       => $this->getDisplayName(),
			'isOwner'           => $this->getIsOwner(),
			'loggedIn'			=> $this->getLoggedIn(),
			'pollId'            => $this->getPollId(),
			'token'             => $this->getToken(),
			'userHasVoted'		=> $this->getUserHasVoted(),
			'userId'            => $this->getUserId(),
			'userIsInvolved'	=> $this->getUserIsInvolved(),
		];
	}

	/**
	 * getLoggedIn - Is user logged in to nextcloud?
	 */
	private function getLoggedIn(): bool {
		return \OC::$server->getUserSession()->isLoggedIn();
	}

	/**
	 * getIsOwner - Is user owner of the poll?
	 */
	private function getIsOwner(): bool {
		return ($this->getLoggedIn() && $this->poll->getOwner() === $this->getUserId());
	}

	/**
	 * getIsAdmin - Is the user admin
	 * Returns true, if user is in admin group
	 */
	private function getIsAdmin(): bool {
		return ($this->getLoggedIn() && $this->groupManager->isAdmin($this->getUserId()));
	}

	/**
	 * hasAdminAccess - Has user administrative rights?
	 * Returns true, if user is in admin group and poll has allowed admins to manage the poll
	 */
	private function hasAdminAccess(): bool {
		return ($this->getIsAdmin() && $this->poll->getAdminAccess());
	}

	/**
	 * getUserIsInvolved - Is user involved?
	 * Returns true, if the current user is involved in the share via share or if he is a participant.
	 */
	private function getUserIsInvolved(): bool {
		return (
			   $this->getIsOwner()
			|| $this->getUserHasVoted()
			|| $this->getGroupShare()
			|| $this->getPersonalShare());
	}

	/**
	 * getUserHasVoted - Is user a participant?
	 * Returns true, if the current user is already a particitipant of the current poll.
	 */
	private function getUserHasVoted(): bool {
		return count(
			$this->voteMapper->findParticipantsVotes($this->getPollId(), $this->getUserId())
		) > 0;
	}

	/**
	 * getGroupShare - Is the poll shared via group share?
	 * Returns true, if the current poll contains a group share with a group,
	 * where the current user is member of. This only affects logged users.
	 */
	private function getGroupShare(): int {
		if (!$this->getLoggedIn()) {
			return 0;
		}
		return count(
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
				if ($item->getType() === Share::TYPE_GROUP && $this->groupManager->isInGroup($this->getUserId(), $item->getUserId())) {
					return true;
				}
			})
		);
	}

	/**
	 * getPersonalShare - Is the poll shared via user share?
	 * Returns >0, if the current poll contains a user share for the current user.
	 * This only affects logged users.
	 */
	private function getPersonalShare(): int {
		if (!$this->getLoggedIn()) {
			return 0;
		}
		return count(
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
				if (in_array($item->getType(), [
					Share::TYPE_USER,
					Share::TYPE_EXTERNAL,
					Share::TYPE_EMAIL,
					Share::TYPE_CONTACT
				])
					&& $item->getUserId() === $this->getUserId()
				) {
					return true;
				}
			})
		);
	}

	private function validateShareAccess(): void {
		if ($this->getLoggedIn()) {
			if (!$this->getValidAuthenticatedShare()) {
				throw new NotAuthorizedException('Share type "' . $this->share->getType() . '"only valid for external users');
			};
		} else {
			if (!$this->getValidPublicShare()) {
				throw new NotAuthorizedException('Share type "' . $this->share->getType() . '"only valid for internal users');
			};
		}
	}

	private function getValidPublicShare(): bool {
		return in_array($this->share->getType(), [
			Share::TYPE_PUBLIC,
			Share::TYPE_EMAIL,
			Share::TYPE_CONTACT,
			Share::TYPE_EXTERNAL
		]);
	}

	private function getValidAuthenticatedShare(): bool {
		return in_array($this->share->getType(), [
			Share::TYPE_PUBLIC,
			Share::TYPE_USER,
			Share::TYPE_GROUP
		]);
	}

	private function hasEmail(): bool {
		if ($this->share->getToken()) {
			return strlen($this->share->getEmailAddress()) > 0;
		} else {
			return $this->getLoggedIn();
		}
	}
}
