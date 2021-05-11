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
	public const PERMISSION_OVERRIDE = 'override_permission';
	public const PERMISSION_POLL_VIEW = 'view';
	public const PERMISSION_POLL_EDIT = 'edit';
	public const PERMISSION_POLL_DELETE = 'delete';
	public const PERMISSION_POLL_RESULTS_VIEW = 'seeResults';
	public const PERMISSION_POLL_USERNAMES_VIEW = 'seeUserNames';
	public const PERMISSION_POLL_TAKEOVER = 'takeOver';
	public const PERMISSION_POLL_SUBSCRIBE = 'subscribe';
	public const PERMISSION_COMMENT_ADD = 'comment';
	public const PERMISSION_OPTIONS_ADD = 'add_options';
	public const PERMISSION_VOTE_EDIT = 'vote';

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
	public function setToken(string $token = '', string $permission = self::PERMISSION_POLL_VIEW): Acl {
		try {
			$this->share = $this->shareMapper->findByToken($token);
			$this->poll = $this->pollMapper->find($this->share->getPollId());
			$this->validateShareAccess();
			$this->request($permission);

		} catch (DoesNotExistException $e) {
			throw new NotAuthorizedException('Error loading share ' . $token);
		}

		return $this;
	}

	public function setPollId(?int $pollId = 0, string $permission = self::PERMISSION_POLL_VIEW): Acl {
		try {
			$this->poll = $this->pollMapper->find($pollId);
			$this->request($permission);
		} catch (DoesNotExistException $e) {
			throw new NotAuthorizedException('Error loading poll ' . $pollId);
		}

		return $this;
	}

	public function getToken(): string {
		return strval($this->share->getToken());
	}

	public function getPollId(): int {
		return $this->poll->getId();
	}

	public function getPoll(): Poll {
		return $this->poll;
	}

	public function getUserId(): string {
		return $this->getLoggedIn() ? \OC::$server->getUserSession()->getUser()->getUID() : $this->share->getUserId();
	}

	public function validateUserId(string $userId): void {
		if ($this->getUserId() !== $userId) {
			throw new NotAuthorizedException;
		}
	}

	/**
	 * getIsOwner - Is user owner of the poll?
	 */
	public function getIsOwner(): bool {
		return ($this->getLoggedIn() && $this->poll->getOwner() === $this->getUserId());
	}

	private function getDisplayName(): string {
		return $this->getLoggedIn() ? $this->userManager->get($this->getUserId())->getDisplayName() : $this->share->getDisplayName();
	}

	public function getIsAllowed(string $permission): bool {
		switch ($permission) {
			case self::PERMISSION_OVERRIDE:
				return true;
			case self::PERMISSION_POLL_VIEW:
			// if ($this->getIsOwner() || $this->hasAdminAccess()) {
				if ($this->getIsAllowed(self::PERMISSION_POLL_EDIT)) {
					return true; // always grant access, if user has edit rights
				}

				if ($this->poll->getDeleted()) {
					return false; // always deny access, if poll is deleted
				}

				if ($this->poll->getAccess() === Poll::ACCESS_PUBLIC) {
					return true; // grant access if poll poll is public
				}

				if ($this->getUserIsInvolved()) {
					return true; // grant access if user is involved in poll in any way
				}

				if ($this->getToken()) {
					return true; // user has token
				}

			case self::PERMISSION_POLL_EDIT:
				return $this->getIsOwner() || $this->hasAdminAccess();

			case self::PERMISSION_POLL_DELETE:
				return $this->getIsAllowed(self::PERMISSION_POLL_EDIT) || $this->getIsAdmin();

			case self::PERMISSION_POLL_TAKEOVER:
				return $this->getIsAdmin() && !$this->getIsOwner();

			case self::PERMISSION_POLL_SUBSCRIBE:
				return $this->getUserHasEmail();

			case self::PERMISSION_POLL_RESULTS_VIEW:
				return $this->getIsOwner()
					|| $this->poll->getShowResults() === Poll::SHOW_RESULTS_ALWAYS
					|| $this->poll->getShowResults() === Poll::SHOW_RESULTS_CLOSED && $this->poll->getExpired();

			case self::PERMISSION_POLL_USERNAMES_VIEW:
				return $this->getIsOwner() || !$this->poll->getAnonymous();

			case self::PERMISSION_OPTIONS_ADD:
				return $this->getIsAllowed(self::PERMISSION_POLL_EDIT)
					|| ($this->poll->getAllowProposals() === Poll::PROPOSAL_ALLOW
					&& !$this->poll->getProposalsExpired());

			case self::PERMISSION_COMMENT_ADD:
				return $this->share->getType() !== Share::TYPE_PUBLIC && $this->poll->getallowComment();

			case self::PERMISSION_VOTE_EDIT:
				return !$this->poll->getExpired() && $this->share->getType() !== Share::TYPE_PUBLIC;

		}

		return false;
	}

	public function request(string $permission): void {
		if (!$this->getIsAllowed($permission)) {
			throw new NotAuthorizedException('denied permission ' . $permission);
		}
	}

	public function jsonSerialize(): array {
		return	[
			'allowComment' => $this->getIsAllowed(self::PERMISSION_COMMENT_ADD),
			'allowAddOptions' => $this->getIsAllowed(self::PERMISSION_OPTIONS_ADD),
			'allowEdit' => $this->getIsAllowed(self::PERMISSION_POLL_EDIT),
			'allowSeeResults' => $this->getIsAllowed(self::PERMISSION_POLL_RESULTS_VIEW),
			'allowSeeUsernames' => $this->getIsAllowed(self::PERMISSION_POLL_USERNAMES_VIEW),
			'allowSubscribe' => $this->getIsAllowed(self::PERMISSION_POLL_SUBSCRIBE),
			'allowView' => $this->getIsAllowed(self::PERMISSION_POLL_VIEW),
			'allowVote' => $this->getIsAllowed(self::PERMISSION_VOTE_EDIT),
			'displayName' => $this->getDisplayName(),
			'isOwner' => $this->getIsOwner(),
			'loggedIn' => $this->getLoggedIn(),
			'pollId' => $this->getPollId(),
			'token' => $this->getToken(),
			'userHasVoted' => $this->getUserHasVoted(),
			'userId' => $this->getUserId(),
			'userIsInvolved' => $this->getUserIsInvolved(),
			'pollExpired' => $this->poll->getExpired(),
			'pollExpire' => $this->poll->getExpire(),
		];
	}

	/**
	 * getLoggedIn - Is user logged in to nextcloud?
	 */
	private function getLoggedIn(): bool {
		return \OC::$server->getUserSession()->isLoggedIn();
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
	 * Returns true, if user is in admin group and poll has allowed admins to manage the poll,
	 * or when running console commands.
	 */
	private function hasAdminAccess(): bool {
		return (($this->getIsAdmin() && $this->poll->getAdminAccess()) || defined('OC_CONSOLE'));
	}

	/**
	 * getUserIsInvolved - Is user involved?
	 * Returns true, if the current user is involved in the poll via share,
	 * as a participant or as the poll owner.
	 */
	private function getUserIsInvolved(): bool {
		return (
			   $this->getIsOwner()
			|| $this->getUserHasVoted()
			|| $this->isInvitedViaGroupShare()
			|| $this->isPersonallyInvited());
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
	 * isInvitedViaGroupShare - Is the poll shared via group share?
	 * Returns true, if the current poll contains a group share with a group,
	 * where the current user is member of. This only affects logged in users.
	 */
	private function isInvitedViaGroupShare(): bool {
		if ($this->getLoggedIn()) {
			return !!count(
				array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
					return ($item->getType() === Share::TYPE_GROUP && $this->groupManager->isInGroup($this->getUserId(), $item->getUserId()));
				})
			);
		}

		return false;
	}

	/**
	 * isPersonallyInvited - Is the poll shared via user share?
	 * Returns true, if the current poll contains a user share for the current user.
	 * This only affects logged in users.
	 */
	private function isPersonallyInvited(): bool {
		if ($this->getLoggedIn()) {
			return !!count(
				array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
					return ($item->getUserId() === $this->getUserId()
						&& in_array($item->getType(), [
							Share::TYPE_USER,
							Share::TYPE_EXTERNAL,
							Share::TYPE_EMAIL,
							Share::TYPE_CONTACT
						])
					);
				})
			);
		}

		return false;
	}

	private function validateShareAccess(): void {
		if ($this->getLoggedIn() && !$this->isShareValidForUsers()) {
			throw new NotAuthorizedException('Share type "' . $this->share->getType() . '"only valid for guests');
		}
		if (!$this->isShareValidForGuests()) {
			throw new NotAuthorizedException('Share type "' . $this->share->getType() . '"only valid for registered users');
		};
	}

	private function isShareValidForGuests(): bool {
		return in_array($this->share->getType(), [
			Share::TYPE_PUBLIC,
			Share::TYPE_EMAIL,
			Share::TYPE_CONTACT,
			Share::TYPE_EXTERNAL
		]);
	}

	private function isShareValidForUsers(): bool {
		return in_array($this->share->getType(), [
			Share::TYPE_PUBLIC,
			Share::TYPE_USER,
			Share::TYPE_GROUP
		]);
	}

	private function getUserHasEmail(): bool {
		return $this->share->getToken() ? strlen($this->share->getEmailAddress()) > 0 : $this->getLoggedIn();
	}
}
