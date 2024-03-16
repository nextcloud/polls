<?php

declare(strict_types=1);
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
use OCA\Polls\AppConstants;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\InsufficientAttributesException;
use OCA\Polls\Exceptions\InvalidPollIdException;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\ISession;

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
	public const PERMISSION_COMMENT_ADD = 'addComment';
	public const PERMISSION_COMMENT_DELETE = 'deleteComment';
	public const PERMISSION_OPTIONS_ADD = 'addOptions';
	public const PERMISSION_OPTION_DELETE = 'deleteOption';
	public const PERMISSION_VOTE_EDIT = 'vote';
	public const PERMISSION_PUBLIC_SHARES = 'publicShares';
	public const PERMISSION_ALL_ACCESS = 'allAccess';

	private ?int $pollId = null;
	private ?UserBase $currentUser = null;

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private AppSettings $appSettings,
		private PollMapper $pollMapper,
		private ISession $session,
		private ShareMapper $shareMapper,
		private UserMapper $userMapper,
		private Poll $poll,
		private Share $share,
	) {
		$this->pollId = null;
	}

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {
		return	[
			'pollId' => $this->getPoll()->getId(),
			'pollExpired' => $this->getPoll()->getExpired(),
			'pollExpire' => $this->getPoll()->getExpire(),
			'currentUser' => $this->getCurrentUserArray(),
			'permissions' => $this->getPermissionsArray(),
		];
	}

	public function getPermissionsArray(): array {
		return [
			'addOptions' => $this->getIsAllowed(self::PERMISSION_OPTIONS_ADD),
			'allAccess' => $this->getIsAllowed(self::PERMISSION_ALL_ACCESS),
			'archive' => $this->getIsAllowed(self::PERMISSION_POLL_ARCHIVE),
			'comment' => $this->getIsAllowed(self::PERMISSION_COMMENT_ADD),
			'delete' => $this->getIsAllowed(self::PERMISSION_POLL_DELETE),
			'edit' => $this->getIsAllowed(self::PERMISSION_POLL_EDIT),
			'pollCreation' => $this->getIsAllowed(self::PERMISSION_POLL_CREATE),
			'pollDownload' => $this->getIsAllowed(self::PERMISSION_POLL_DOWNLOAD),
			'publicShares' => $this->getIsAllowed(self::PERMISSION_PUBLIC_SHARES),
			'seeResults' => $this->getIsAllowed(self::PERMISSION_POLL_RESULTS_VIEW),
			'seeUsernames' => $this->getIsAllowed(self::PERMISSION_POLL_USERNAMES_VIEW),
			'seeMailAddresses' => $this->getIsAllowed(self::PERMISSION_POLL_MAILADDRESSES_VIEW),
			'subscribe' => $this->getIsAllowed(self::PERMISSION_POLL_SUBSCRIBE),
			'view' => $this->getIsAllowed(self::PERMISSION_POLL_VIEW),
			'vote' => $this->getIsAllowed(self::PERMISSION_VOTE_EDIT),
		];
	}

	public function getCurrentUserArray(): array {
		return [
			'displayName' => $this->getCurrentUser()->getDisplayName(),
			'hasVoted' => $this->getIsParticipant(),
			'isInvolved' => $this->getIsInvolved(),
			'isLoggedIn' => $this->getCurrentUser()->getIsLoggedIn(),
			'isNoUser' => !$this->getCurrentUser()->getIsLoggedIn(),
			'isOwner' => $this->getIsPollOwner(),
			'userId' => $this->getUserId(),
		];
	}
	/**
	 * Setters
	 */

	/**
	 * Set poll id and load poll
	 */
	public function setPollId(int $pollId, string $permission = self::PERMISSION_POLL_VIEW): void {
		if ($this->isSessionTokenSet()) {
			if ($pollId !== $this->getShare()->getPollId()) {
				throw new ForbiddenException('pollId does not match share');
			}
		} else {
			$this->pollId = $pollId;
		}
		
		$this->getPoll();
		$this->request($permission);
	}

	/**
	 * get poll
	 * @throws InsufficientAttributesException Thrown if stored pollId is null
	 */
	public function getPoll(): Poll {
		if ($this->pollId === null) {
			throw new InsufficientAttributesException('PollId may not be mull');
		}

		if ($this->pollId !== $this->poll->getId()) {
			$this->poll = $this->pollMapper->find($this->pollId);
		}

		// sideload existing share for internal user, if no token is set
		$this->sideLoadShare();

		return $this->poll;
	}

	/**
	 * Get share
	 * load share from db by session stored token or rely on cached share
	 */
	private function getShare(): Share {
		if ($this->validateShareToken()) {
			$this->share = $this->shareMapper->findByToken((string) $this->getToken());
			$this->pollId = $this->share->getPollId();
		}

		return $this->share;
	}

	private function validateShareToken(): bool {
		return $this->isSessionTokenSet() && $this->getToken() !== $this->share->getToken();
	}

	private function isSessionTokenSet(): bool {
		return boolval($this->getToken());
	}

	private function sideLoadShare(): void {
		// do not load if share is loaded via public access
		if ($this->isSessionTokenSet()) {
			return;
		}
		
		// do not load if user is not logged in
		if (!$this->getCurrentUser()->getIsLoggedIn()) {
			return;
		}

		// do not load if a share is already loaded and matches the current poll id
		if (boolval($this->share->getPollId() === $this->poll->getId())) {
			return;
		}
		
		// find share, which grants access or poll admin rights
		try {
			$this->share = $this->shareMapper->findByPollAndUser(
				$this->poll->getId(),
				$this->getCurrentUser()->getId()
			);
		} catch (ShareNotFoundException $e) {
			$this->share = new Share();
		}
		
	}

	/**
	 * loads the current user from the userMapper or returns the cached one
	 */
	private function getCurrentUser(): UserBase {
		$this->currentUser = $this->userMapper->getCurrentUser();
		return $this->currentUser;
	}

	/**
	 * returns the current pollId; Either from share or from setPollId()
	 */
	public function getPollId(): int {
		if ($this->isSessionTokenSet()) {
			$this->pollId = $this->getShare()->getPollId();
		}
		if (!$this->getPoll()->getId()) {
			throw new InvalidPollIdException('No pollId set!');
		}
		return $this->getPoll()->getId();
	}

	private function getToken(): ?string {
		return $this->session->get(AppConstants::SESSION_KEY_SHARE_TOKEN);
	}

	/**
	 * Shortcut for currentUser->userId
	 */
	public function getUserId(): string {
		return $this->getCurrentUser()->getId();
	}

	/**
	 * Checks, if the current user is the poll owner
	 */
	public function getIsPollOwner(): bool {
		return ($this->getPoll()->getOwner() === $this->getUserId());
	}

	/**
	 * Check perticular rights and inform via boolean value, if the right is granted  or denied
	 */
	public function getIsAllowed(string $permission): bool {
		$this->getShare();
		// $this->verifyConstraints();
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
			self::PERMISSION_OPTION_DELETE => $this->getAllowDeleteOption(),
			self::PERMISSION_COMMENT_ADD => $this->getAllowComment(),
			self::PERMISSION_COMMENT_DELETE => $this->getAllowDeleteComment(),
			self::PERMISSION_VOTE_EDIT => $this->getAllowVote(),
			default => false,
		};
	}

	/**
	 * Request a permission level and get exception if denied
	 * @throws ForbiddenException Thrown if access is denied
	 */
	public function request(string $permission): void {
		if (!$this->getIsAllowed($permission)) {
			throw new ForbiddenException('denied permission ' . $permission);
		}
	}

	/**
	 * getIsInvolved - Is current user involved in current poll?
	 * @return bool Returns true, if the current user is involved in the poll via share, as a participant or as the poll owner.
	 */
	private function getIsInvolved(): bool {
		return (
			$this->getIsPollOwner()
			|| $this->getIsParticipant()
			|| $this->getIsInvitedViaGroupShare()
			|| $this->getIsInvitedViaGroupShare()
			|| $this->getIsPersonallyInvited());
	}

	/**
	 * Check, if poll settings is set to open access for internal users
	 */
	private function getIsOpenPoll(): bool {
		return $this->getPoll()->getAccess() === Poll::ACCESS_OPEN && $this->getCurrentUser()->getIsLoggedIn();
	}

	/**
	 * getIsParticipant - Is user a participant?
	 * @return bool Returns true, if the current user is already a particitipant of the current poll.
	 */
	private function getIsParticipant(): bool {
		return $this->getPoll()->getCurrentUserCountVotes() > 0;
	}

	/**
	 * getIsInvitedViaGroupShare - Is the poll shared via group share?
	 * where the current user is member of. This only affects logged in users.
	 * @return bool Returns true, if the current poll contains a group share with a group,
	 */
	private function getIsInvitedViaGroupShare(): bool {
		if (!$this->getCurrentUser()->getIsLoggedIn()) {
			return false;
		}

		return count(
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
				return ($item->getType() === Share::TYPE_GROUP && $this->getCurrentUser()->getIsInGroup($item->getUserId()));
			})
		) > 0;
	}

	/**
	 * getIsPersonallyInvited - Is the poll shared via user share?
	 * This only affects logged in users.
	 * @return bool  Returns true, if the current poll contains a user share for the current user.
	 */
	private function getIsPersonallyInvited(): bool {
		if ($this->getCurrentUser()->getIsLoggedIn() && $this->getShare()->getToken()) {
			return in_array($this->getShare()->getType(), [
				Share::TYPE_ADMIN,
				Share::TYPE_USER,
				Share::TYPE_EXTERNAL,
				Share::TYPE_EMAIL,
				Share::TYPE_CONTACT
			]);
		}
		return false;
	}

	/**
	 * The detailed checks - For the sake of readability, the queries and selections
	 * were kept detailed and with low complexity
	 */
	
	/**
	 * Checks, if the user has delegated admin rights to edit poll settings via share
	 */
	private function getIsDelegatedAdmin(): bool {
		return $this->getShare()->getType() === Share::TYPE_ADMIN && !boolval($this->getShare()->getLocked());
	}

	/**
	 * Checks, if user is allowed to edit the poll configuration
	 **/
	private function getAllowEditPoll(): bool {
		// Console has god mode
		if (defined('OC_CONSOLE')) {
			return true;
		}

		// owner is always allowed to edit the poll configuration
		if ($this->getIsPollOwner()) {
			return true;
		}

		// user has delegated owner rights
		if ($this->getIsDelegatedAdmin()) {
			return true;
		}

		// deny edit rights in all other cases
		return false;
	}

	/**
	 * Checks, if user is allowed to access poll
	 */
	private function getAllowAccessPoll(): bool {
		// edit rights include access to poll
		if ($this->getAllowEditPoll()) {
			return true;
		}

		// No further access to poll, if it is deleted
		if ($this->getPoll()->getDeleted()) {
			return false;
		}

		// grant access if user is involved in poll in any way
		if ($this->getIsInvolved()) {
			return true;
		}
		
		// grant access if poll poll is an open poll (for logged in users)
		if ($this->getIsOpenPoll()) {
			return true;
		}

		// return check result of an existing valid share for this user
		return boolval($this->getShare()->getToken());
	}

	/**
	 * Checks, if user is allowed to delete the poll
	 * includes the right to archive and take over
	 **/
	private function getAllowDeletePoll(): bool {
		if ($this->getAllowEditPoll()) {
			// users with edit rights are allowed to delete the poll
			return true;
		}

		// admins are allowed to delete polls, in all other cases deny poll deletion right
		return $this->getCurrentUser()->getIsAdmin();
	}

	/**
	 * Checks, if user is allowed to add add vote options
	 **/
	private function getAllowAddOptions(): bool {
		// Edit right includes adding new options
		if ($this->getAllowEditPoll()) {
			return true;
		}

		// deny, if user has no access right to this poll
		if (!$this->getAllowAccessPoll()) {
			return false;
		}

		// public shares are not allowed to add options
		if ($this->getShare()->getType() === Share::TYPE_PUBLIC) {
			return false;
		}

		// Request for option proposals is expired, deny
		if ($this->getPoll()->getProposalsExpired()) {
			return false;
		}

		// Request for option proposals is expired, deny
		if (boolval($this->getShare()->getLocked())) {
			return false;
		}

		// Allow, if poll requests proposals
		return $this->getPoll()->getAllowProposals() === Poll::PROPOSAL_ALLOW;
	}
	
	/**
	 * Is current user allowed to delete options from poll
	 */
	private function getAllowDeleteOption(): bool {
		return $this->getIsPollOwner() || $this->getIsDelegatedAdmin();
	}

	/**
	 * Compare $userId with current user's id
	 */
	public function matchUser(string $userId): bool {
		return $this->getCurrentUser()->getId() === $userId;
	}

	/**
	 * Checks, if user is allowed to see and write comments
	 **/
	private function getAllowComment(): bool {
		// user has no access right to this poll
		if (!$this->getAllowAccessPoll()) {
			return false;
		}

		// public shares are not allowed to comment
		if ($this->getShare()->getType() === Share::TYPE_PUBLIC) {
			return false;
		}

		// public shares are not allowed to comment
		if (boolval($this->getShare()->getLocked())) {
			return false;
		}

		return (bool) $this->getPoll()->getAllowComment();
	}

	/**
	 * Checks, if user is allowed to delete comments from poll
	 **/
	private function getAllowDeleteComment(): bool {
		return $this->getIsPollOwner() || $this->getIsDelegatedAdmin();
	}

	/**
	 * Checks, if user is allowed to vote
	 **/
	private function getAllowVote(): bool {
		// user has no access right to this poll
		if (!$this->getAllowAccessPoll()) {
			return false;
		}

		// public shares are not allowed to vote
		if ($this->getShare()->getType() === Share::TYPE_PUBLIC) {
			return false;
		}

		// public shares are not allowed to vote
		if (boolval($this->getShare()->getLocked())) {
			return false;
		}

		// deny votes, if poll is expired
		return !$this->getPoll()->getExpired();
	}

	/**
	 * Checks, if user is allowed to subscribe to updates
	 **/
	private function getAllowSubscribeToPoll(): bool {
		// user has no access right to this poll
		if (!$this->getAllowAccessPoll()) {
			return false;
		}

		return $this->getCurrentUser()->getHasEmail();
	}

	/**
	 * Checks, if user is allowed to see results of current poll
	 **/
	private function getShowResults(): bool {
		// edit rights include access to results
		if ($this->getAllowEditPoll()) {
			return true;
		}

		// no access to poll, deny
		if (!$this->getAllowAccessPoll()) {
			return false;
		}

		// show results, when poll is cloed
		if ($this->getPoll()->getShowResults() === Poll::SHOW_RESULTS_CLOSED && $this->getPoll()->getExpired()) {
			return true;
		}

		// return poll settings
		return $this->getPoll()->getShowResults() === Poll::SHOW_RESULTS_ALWAYS;
	}
}
