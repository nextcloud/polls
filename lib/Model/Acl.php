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
	 * setToken - load share via token and than call setShare
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

		// load poll, if pollId does not match
		if ($this->share->getPollId() !== $this->poll->getId()) {
			$this->setPollId($share->getPollId());
		}
		return $this;
	}

	/**
	 * setPollId
	 */
	public function setPollId(int $pollId = 0): Acl {
		try {
			return $this->setPoll($this->pollMapper->find($pollId));
		} catch (DoesNotExistException $e) {
			throw new NotAuthorizedException('Error loading poll ' . $pollId);
		}
	}

	/**
	 * 	 * setPoll
	 *
	 * @return static
	 */
	public function setPoll(Poll $poll): self {
		$this->poll = $poll;
		$this->requestView();
		return $this;
	}

	/**
	 * getUserId
	 */
	public function getUserId() {
		if ($this->getLoggedIn()) {
			return \OC::$server->getUserSession()->getUser()->getUID();
		} else {
			return $this->share->getUserId();
		}
	}

	/**
	 * 	 * getDisplayName
	 *
	 * @return string
	 */
	private function getDisplayName(): string {
		if ($this->getLoggedIn()) {
			return $this->userManager->get($this->getUserId())->getDisplayName();
		} else {
			return $this->share->getDisplayName();
		}
	}

	/**
	 * getPollId
	 */
	public function getPollId(): int {
		return $this->poll->getId();
	}

	/**
	 * getAllowView
	 */
	public function getAllowView(): bool {
		return (
			   $this->getAllowEdit()
			|| !$this->poll->getDeleted() && (
				   $this->getValidPublicShare()
				|| $this->getUserIsInvolved()
				|| $this->getPublicShare()
			)
		);
	}

	/**
	 * getAllowVote
	 */
	public function getAllowVote(): bool {
		return ($this->getAllowView() || $this->getToken())
			&& !$this->poll->getExpired()
			&& !$this->poll->getDeleted()
			&& $this->getUserId();
	}

	/**
	 * getAllowSubscribe
	 */
	public function getAllowSubscribe(): bool {
		return ($this->hasEmail())
			&& !$this->poll->getDeleted()
			&& $this->getAllowView();
	}

	/**
	 * getAllowComment
	 */
	public function getAllowComment(): bool {
		return !$this->poll->getDeleted() && $this->getUserId();
	}

	/**
	 * getAllowEdit
	 */
	public function getAllowEdit(): bool {
		return ($this->getIsOwner() || $this->getIsAdmin());
	}

	/**
	 * requestView
	 */
	public function requestView(): void {
		if (!$this->getAllowView()) {
			throw new NotAuthorizedException;
		}
	}

	/**
	 * requestVote
	 */
	public function requestVote(): void {
		if (!$this->getAllowVote()) {
			throw new NotAuthorizedException;
		}
	}

	/**
	 * requestComment
	 */
	public function requestComment(): void {
		if (!$this->getAllowComment()) {
			throw new NotAuthorizedException;
		}
	}

	/**
	 * requestEdit
	 */
	public function requestEdit(): void {
		if (!$this->getAllowEdit()) {
			throw new NotAuthorizedException;
		}
	}

	/**
	 * requestDelete
	 */
	public function requestDelete(): void {
		if (!$this->getAllowEdit() || !$this->poll->getDeleted()) {
			throw new NotAuthorizedException;
		}
	}

	/**
	 * validateUserId
	 */
	public function validateUserId(string $userId): void {
		if ($this->getUserId() !== $userId) {
			throw new NotAuthorizedException;
		}
	}

	/**
	 * getAllowSeeResults
	 */
	public function getAllowSeeResults(): bool {
		return $this->poll->getShowResults() === Poll::SHOW_RESULTS_ALWAYS
			|| ($this->poll->getShowResults() === 'expired' && $this->poll->getExpired())
			|| $this->getIsOwner();
	}

	/**
	 * getAllowSeeUsernames
	 */
	public function getAllowSeeUsernames(): bool {
		return !$this->poll->getAnonymous() || $this->getIsOwner();
	}

	/**
	 * getToken
	 */
	public function getToken(): string {
		return strval($this->share->getToken());
	}

	public function jsonSerialize(): array {
		return	[
			'allowComment'      => $this->getAllowComment(),
			'allowEdit'         => $this->getAllowEdit(),
			'allowSeeResults'   => $this->getAllowSeeResults(),
			'allowSeeUsernames' => $this->getAllowSeeUsernames(),
			'allowSubscribe'    => $this->getAllowSubscribe(),
			'allowView'         => $this->getAllowView(),
			'allowVote'         => $this->getAllowVote(),
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
	 * getIsAdmin - Has user administrative rights?
	 * Returns true, if user is in admin group and poll has allowed admins to manage the poll
	 */
	private function getIsAdmin(): bool {
		return ($this->getLoggedIn() && $this->groupManager->isAdmin($this->getUserId()) && $this->poll->getAdminAccess());
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

	/**
	 * getPublicShare
	 */
	private function getPublicShare(): int {
		return count(
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
				if ($item->getType() === Share::TYPE_PUBLIC && $item->getToken() === $this->getToken()) {
					return true;
				}
			})
		);
	}

	/**
	 * validateShareAccess
	 */
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

	/**
	 * getValidPublicShare
	 */
	private function getValidPublicShare(): bool {
		return in_array($this->share->getType(), [
			Share::TYPE_PUBLIC,
			Share::TYPE_EMAIL,
			Share::TYPE_CONTACT,
			Share::TYPE_EXTERNAL
		]);
	}

	/**
	 * getValidAuthenticatedShare
	 */
	private function getValidAuthenticatedShare(): bool {
		return in_array($this->share->getType(), [
			Share::TYPE_PUBLIC,
			Share::TYPE_USER,
			Share::TYPE_GROUP
		]);
	}

	/**
	 * hasEmail
	 */
	private function hasEmail(): bool {
		if ($this->share->getToken()) {
			return strlen($this->share->getEmailAddress()) > 0;
		} else {
			return $this->getLoggedIn();
		}
	}
}
