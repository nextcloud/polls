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


namespace OCA\Polls\Model;

use JsonSerializable;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\InvalidMethodCallException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;

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
	public const PERMISSION_POLL_ARCHIVE = 'archive';
	public const PERMISSION_POLL_RESULTS_VIEW = 'seeResults';
	public const PERMISSION_POLL_MAILADDRESSES_VIEW = 'seeMailAddresses';
	public const PERMISSION_POLL_USERNAMES_VIEW = 'seeUserNames';
	public const PERMISSION_POLL_TAKEOVER = 'takeOver';
	public const PERMISSION_POLL_SUBSCRIBE = 'subscribe';
	public const PERMISSION_POLL_CREATE = 'pollCreate';
	public const PERMISSION_POLL_DOWNLOAD = 'pollDownload';
	public const PERMISSION_COMMENT_ADD = 'comment';
	public const PERMISSION_OPTIONS_ADD = 'add_options';
	public const PERMISSION_VOTE_EDIT = 'vote';
	public const PERMISSION_PUBLIC_SHARES = 'publicShares';
	public const PERMISSION_ALL_ACCESS = 'allAccess';

	
	public function __construct(
		private IUserManager $userManager,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
		private OptionMapper $optionMapper,
		private PollMapper $pollMapper,
		private VoteMapper $voteMapper,
		private ShareMapper $shareMapper,
		private AppSettings $appSettings,
		private ?Poll $poll,
		private ?Share $share,
	) {
		$this->poll = null;
		$this->share = null;
		$this->appSettings = new AppSettings;
	}

	public function jsonSerialize(): array {
		return	[
			'allowAddOptions' => $this->getIsAllowed(self::PERMISSION_OPTIONS_ADD),
			'allowAllAccess' => $this->getIsAllowed(self::PERMISSION_ALL_ACCESS),
			'allowArchive' => $this->getIsAllowed(self::PERMISSION_POLL_ARCHIVE),
			'allowComment' => $this->getIsAllowed(self::PERMISSION_COMMENT_ADD),
			'allowDelete' => $this->getIsAllowed(self::PERMISSION_POLL_DELETE),
			'allowEdit' => $this->getIsAllowed(self::PERMISSION_POLL_EDIT),
			'allowPollCreation' => $this->getIsAllowed(self::PERMISSION_POLL_CREATE),
			'allowPollDownload' => $this->getIsAllowed(self::PERMISSION_POLL_DOWNLOAD),
			'allowPublicShares' => $this->getIsAllowed(self::PERMISSION_PUBLIC_SHARES),
			'allowSeeResults' => $this->getIsAllowed(self::PERMISSION_POLL_RESULTS_VIEW),
			'allowSeeUsernames' => $this->getIsAllowed(self::PERMISSION_POLL_USERNAMES_VIEW),
			'allowSeeMailAddresses' => $this->getIsAllowed(self::PERMISSION_POLL_MAILADDRESSES_VIEW),
			'allowSubscribe' => $this->getIsAllowed(self::PERMISSION_POLL_SUBSCRIBE),
			'allowView' => $this->getIsAllowed(self::PERMISSION_POLL_VIEW),
			'allowVote' => $this->getIsAllowed(self::PERMISSION_VOTE_EDIT),
			'displayName' => $this->getDisplayName(),
			'isOwner' => $this->getIsOwner(),
			'isVoteLimitExceeded' => $this->getIsVoteLimitExceeded(),
			'loggedIn' => $this->getIsLoggedIn(),
			'isNoUser' => !$this->getIsLoggedIn(),
			'isGuest' => !$this->getIsLoggedIn(),
			'pollId' => $this->getPollId(),
			'token' => $this->getToken(),
			'userHasVoted' => $this->getIsParticipant(),
			'userId' => $this->getUserId(),
			'userIsInvolved' => $this->getIsInvolved(),
			'pollExpired' => $this->poll->getExpired(),
			'pollExpire' => $this->poll->getExpire(),
		];
	}

	/**
	 * Setters
	 */

	/**
	 * Set share token and load share if neccessary
	 * All ends with self::setpoll(), where the permission is checked
	 */
	public function setToken(string $token = '', string $permission = self::PERMISSION_POLL_VIEW): Acl {
		try {
			if ($this->share?->$token === $token) {											// share matching the requested token is already loaded
				$this->setPollId($this->share->getPollId(), $permission);					// Set the poll Id to verify the correct poll gets loaded and permissions get checked
			} else {
				$this->setShare($this->shareMapper->findByToken($token), $permission);		// load the share mathing the requested token
			}
		} catch (ShareNotFoundException $e) {
			throw new NotFoundException('Error loading share ' . $token);
		}
		
		return $this;
	}

	/**
	 * Set share and load poll
	 * All ends with self::setPoll(), where the permission is checked
	 */
	public function setShare(Share $share, string $permission = self::PERMISSION_POLL_VIEW): Acl {
		$this->share = $share;

		$this->validateShareAccess();														// check, if share is allowed for the user type
		$this->setPollId($this->share->getPollId(), $permission);							// set the poll id to laod the poll corresponding to the share and check permissions

		return $this;
	}

	/**
	 * Set poll id and load poll
	 */
	public function setPollId(int $pollId = 0, string $permission = self::PERMISSION_POLL_VIEW): Acl {
		try {
			if ($this->poll?->getPollId() !== $pollId) {
				$this->setPoll($this->pollMapper->find($pollId), $permission);					// load requested poll
			} else {
				$this->request($permission);													// just check the permissions in all cases
			}

		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Error loading poll with id ' . $pollId);
		}
		
		return $this;
	}

	/**
	 * Set poll
	 */
	public function setPoll(Poll $poll, string $permission = self::PERMISSION_POLL_VIEW): Acl {
		$this->poll = $poll;
		$this->loadShare();
		$this->request($permission);
		return $this;
	}

	/**
	 * Property getters
	 */
	public function getPoll(): Poll|null {
		return $this->poll;
	}

	public function getPollId(): int {
		return $this->poll->getId();
	}

	public function getToken(): string {
		return strval($this->share?->getToken());
	}

	public function getTokenIsValid(): bool {
		return boolval($this->share?->getToken());
	}

	public function getUserId(): string {
		return $this->userSession->getUser()?->getUID() ?? $this->share->getUserId();
	}

	private function getDisplayName(): string {
		return ($this->getIsLoggedIn() ? $this->userManager->get($this->getUserId())?->getDisplayName() : $this->share->getDisplayName()) ?? '';
	}

	/**
	 * Validations
	 */

	public function getIsOwner(): bool {
		return ($this->getIsLoggedIn() && $this->poll->getOwner() === $this->getUserId());
	}

	public function validateUserId(string $userId): void {
		if ($this->getUserId() !== $userId) {
			throw new ForbiddenException('User id does not match.');
		}
	}

	public function validatePollId(int $pollId): void {
		if ($this->getPollId() !== $pollId) {
			throw new ForbiddenException('Poll id does not match.');
		}
	}

	private function validateShareAccess(): void {
		if ($this->getIsLoggedIn() && !$this->getIsShareValidForUsers()) {
			throw new ForbiddenException('Share type "' . $this->share->getType() . '" is only valid for guests');
		}
		if (!$this->getIsShareValidForGuests()) {
			throw new ForbiddenException('Share type "' . $this->share->getType() . '" is only valid for registered users');
		};
	}

	public function getIsAllowed(string $permission): bool {
		return match ($permission) {
			self::PERMISSION_OVERRIDE => true,
			self::PERMISSION_POLL_CREATE => $this->appSettings->getPollCreationAllowed(),
			self::PERMISSION_POLL_MAILADDRESSES_VIEW => $this->appSettings->getAllowSeeMailAddresses(),
			self::PERMISSION_POLL_DOWNLOAD => $this->appSettings->getPollDownloadAllowed(),
			self::PERMISSION_ALL_ACCESS => $this->appSettings->getAllAccessAllowed(),
			self::PERMISSION_PUBLIC_SHARES => $this->appSettings->getPublicSharesAllowed(),
			self::PERMISSION_POLL_VIEW => $this->getAllowAccessPoll(),
			self::PERMISSION_POLL_EDIT => $this->getAllowEditPoll(),
			self::PERMISSION_POLL_DELETE => $this->getAllowDeletePoll(),
			self::PERMISSION_POLL_ARCHIVE => $this->getAllowDeletePoll(),
			self::PERMISSION_POLL_TAKEOVER => $this->getAllowDeletePoll(),
			self::PERMISSION_POLL_SUBSCRIBE => $this->getAllowSubscribeToPoll(),
			self::PERMISSION_POLL_RESULTS_VIEW => $this->getShowResults(),
			self::PERMISSION_POLL_USERNAMES_VIEW => $this->getAllowEditPoll() || !$this->poll->getAnonymous(),
			self::PERMISSION_OPTIONS_ADD => $this->getAllowAddOptions(),
			self::PERMISSION_COMMENT_ADD => $this->getAllowComment(),
			self::PERMISSION_VOTE_EDIT => $this->getAllowVote(),
			default => false,
		};
	}

	public function request(string $permission): void {
		if (!$this->getIsAllowed($permission)) {
			throw new ForbiddenException('denied permission ' . $permission);
		}
	}

	public function getIsVoteLimitExceeded(): bool {
		// return true, if no vote limit is set
		if ($this->getPoll()->getVoteLimit() < 1) {
			return false;
		}

		// Only count votes, which match to an actual existing option.
		// Explanation: If an option is deleted, the corresponding votes are not deleted.
		$pollOptionTexts = array_map(function ($option) {
			return $option->getPollOptionText();
		}, $this->optionMapper->findByPoll($this->getPollId()));

		$voteCount = 0;
		$votes = $this->voteMapper->getYesVotesByParticipant($this->getPollId(), $this->getUserId());
		foreach ($votes as $vote) {
			if (in_array($vote->getVoteOptionText(), $pollOptionTexts)) {
				$voteCount++;
			}
		}
		
		if ($this->getPoll()->getVoteLimit() <= $voteCount) {
			return true;
		}
		return false;
	}

	/**
	 * getIsLogged - Is user logged in to nextcloud?
	 */
	public function getIsLoggedIn(): bool {
		return $this->userSession->isLoggedIn();
	}

	/**
	 * getIsAdmin - Is the user admin
	 * Returns true, if user is in admin group
	 */
	private function getIsAdmin(): bool {
		return ($this->getIsLoggedIn() && $this->groupManager->isAdmin($this->getUserId()));
	}

	/**
	 * getIsInvolved - Is user involved?
	 * Returns true, if the current user is involved in the poll via share,
	 * as a participant or as the poll owner.
	 */
	private function getIsInvolved(): bool {
		return (
			$this->getIsOwner()
			|| $this->getIsParticipant()
			|| $this->getIsInvitedViaGroupShare()
			|| $this->getIsPersonallyInvited());
	}

	/**
	 * getIsParticipant - Is user a participant?
	 * Returns true, if the current user is already a particitipant of the current poll.
	 */
	private function getIsParticipant(): bool {
		return count(
			$this->voteMapper->findParticipantsVotes($this->getPollId(), $this->getUserId())
		) > 0;
	}

	/**
	 * getIsInvitedViaGroupShare - Is the poll shared via group share?
	 * Returns true, if the current poll contains a group share with a group,
	 * where the current user is member of. This only affects logged in users.
	 */
	private function getIsInvitedViaGroupShare(): bool {
		if (!$this->getIsLoggedIn()) {
			return false;
		}

		return 0 < count(
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
				return ($item->getType() === Share::TYPE_GROUP && $this->groupManager->isInGroup($this->getUserId(), $item->getUserId()));
			})
		);
	}

	/**
	 * getIsPersonallyInvited - Is the poll shared via user share?
	 * Returns true, if the current poll contains a user share for the current user.
	 * This only affects logged in users.
	 */
	private function getIsPersonallyInvited(): bool {
		if ($this->getIsLoggedIn() && $this->share) {
			return in_array($this->share->getType(), [
				Share::TYPE_ADMIN,
				Share::TYPE_USER,
				Share::TYPE_EXTERNAL,
				Share::TYPE_EMAIL,
				Share::TYPE_CONTACT
			]);
		}
		return false;
	}

	private function getIsDelegatedAdmin(): bool {
		if (!$this->getIsLoggedIn()) {
			return false;
		}

		if ($this->loadShare()) {							// load share, if not loaded
			return $this->share->getType() === Share::TYPE_ADMIN && !$this->share->getRevoked();
		};
		return false;
	}

	private function getIsShareValidForGuests(): bool {
		return in_array($this->share->getType(), Share::SHARE_PUBLIC_ACCESS_ALLOWED);
	}

	private function getIsShareValidForUsers(): bool {
		return in_array($this->share->getType(), Share::SHARE_AUTH_ACCESS_ALLOWED);
	}

	private function getHasEmail(): bool {
		return $this->share?->getToken() ? strlen($this->share->getEmailAddress()) > 0 : $this->getIsLoggedIn();
	}

	/**
	 * Load share for access checks, if it is not already loaded
	 **/
	private function loadShare(): bool {
		if (!$this->poll) {
			throw new InvalidMethodCallException('Loading share only possible with loaded poll');
		}

		try {
			if ($this->share?->getUserId() !== $this->getUserId() || $this->share?->getPollId() !== $this->poll->getId()) {
				$this->share = $this->shareMapper->findByPollAndUser($this->poll->getId(), $this->getUserId());
			}
		} catch (\Throwable $th) {
			$this->share = null;
			return false;
		}

		return $this->share !== null;
	}

	/**
	 * Checks, if user is allowed to edit the poll configuration
	 **/
	private function getAllowEditPoll(): bool {
		if (defined('OC_CONSOLE')) {
			return true;										// Console god mode
		}

		if ($this->getIsOwner()) {
			return true;										// owner is always allowed to edit the poll configuration
		}

		if ($this->getIsDelegatedAdmin()) {
			return true;										// user has delegated owner rights
		}

		return false;											// deny edit rights in all other cases
	}

	/**
	 * Checks, if user is allowed to access poll
	 **/
	private function getAllowAccessPoll(): bool {
		if ($this->getAllowEditPoll()) {
			return true;										// edit rights include access to poll
		}

		if ($this->poll->getDeleted()) {
			return false;										// No further access to poll, if it is deleted
		}

		if ($this->getIsInvolved()) {
			return true;										// grant access if user is involved in poll in any way
		}
		
		if ($this->poll->getAccess() === Poll::ACCESS_OPEN && $this->getIsLoggedIn()) {
			return true;										// grant access if poll poll is an open poll (for logged in users)
		}

		return $this->getTokenIsValid();						// return check result of an existing valid share for this user
	}

	/**
	 * Checks, if user is allowed to delete the poll
	 * includes the right to archive and take over
	 **/
	private function getAllowDeletePoll(): bool {
		if ($this->getAllowEditPoll()) {
			return true;										// users with edit rights are allowed to delete the poll
		}

		return $this->getIsAdmin();								// admins are allowed to delete polls, in all other cases deny poll deletion right
	}

	/**
	 * Checks, if user is allowed to add add vote options
	 **/
	private function getAllowAddOptions(): bool {
		if ($this->getAllowEditPoll()) {
			return true;													// Edit right includes adding new options
		}

		if (!$this->getAllowAccessPoll()) {
			return false;													// deny, if user has no access right to this poll
		}

		if ($this->share?->getType() === Share::TYPE_PUBLIC) {
			return false;													// public shares are not allowed to add options
		}

		if ($this->poll->getProposalsExpired()) {
			return false;													// Request for option proposals is expired, deny
		}

		if ($this->share?->getRevoked()) {
			return false;													// Request for option proposals is expired, deny
		}

		return $this->poll->getAllowProposals() === Poll::PROPOSAL_ALLOW;	// Allow, if poll requests proposals
	}

	/**
	 * Checks, if user is allowed to see and write comments
	 **/
	private function getAllowComment(): bool {
		if (!$this->getAllowAccessPoll()) {
			return false;											// user has no access right to this poll
		}

		if ($this->share?->getType() === Share::TYPE_PUBLIC) {
			return false;											// public shares are not allowed to comment
		}

		if ($this->share?->getRevoked()) {
			return false;											// public shares are not allowed to comment
		}

		return (bool) $this->poll->getAllowComment();
	}

	/**
	 * Checks, if user is allowed to comment
	 **/
	private function getAllowVote(): bool {
		if (!$this->getAllowAccessPoll()) {
			return false;											// user has no access right to this poll
		}

		if ($this->share?->getType() === Share::TYPE_PUBLIC) {
			return false;											// public shares are not allowed to vote
		}

		if ($this->share?->getRevoked()) {
			return false;											// public shares are not allowed to vote
		}

		return !$this->poll->getExpired();							// deny votes, if poll is expired
	}

	private function getAllowSubscribeToPoll(): bool {
		if (!$this->getAllowAccessPoll()) {
			return false;											// user has no access right to this poll
		}

		return $this->getHasEmail();
	}

	private function getShowResults(): bool {
		if ($this->getAllowEditPoll()) {
			return true;											// edit rights include access to results
		}

		if (!$this->getAllowAccessPoll()) {
			return false;											// no access to poll, deny
		}

		if ($this->poll->getShowResults() === Poll::SHOW_RESULTS_CLOSED && $this->poll->getExpired()) {
			return true;											// show results, when poll is cloed
		}

		return $this->poll->getShowResults() === Poll::SHOW_RESULTS_ALWAYS;
	}
}
