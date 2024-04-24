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
use OCA\Polls\Exceptions\InvalidPollIdException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Model\Settings\AppSettings;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\ISession;
use Psr\Log\LoggerInterface;

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
	// Cache whether the current poll has shares
	private bool $noShare = false;


	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		private AppSettings $appSettings,
		private LoggerInterface $logger,
		private PollMapper $pollMapper,
		private ISession $session,
		private ShareMapper $shareMapper,
		private UserMapper $userMapper,
		private ?Poll $poll = null,
		private ?Share $share = null,
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
			'vote' => $this->getIsAllowed(self::PERMISSION_VOTE_EDIT)
		];
	}
	public function getCurrentUserArray(): array {
		return [
			'displayName' => $this->getDisplayName(),
			'hasVoted' => $this->getIsParticipant(),
			'isInvolved' => $this->getIsInvolved(),
			'isLoggedIn' => $this->getIsLoggedIn(),
			'isNoUser' => !$this->getIsLoggedIn(),
			'isOwner' => $this->getIsOwner(),
			'userId' => $this->getUserId(),
		];
	}
	/**
	 * Setters
	 */

	/**
	 * Set poll id and load poll
	 */
	public function setPollId(?int $pollId = null, string $permission = self::PERMISSION_POLL_VIEW): Acl {
		if ($this->getSessionStoredShareToken() && $pollId !== $this->getShare()->getPollId()) {
			$this->logger->warning('Ignoring requested pollId ' . $pollId . '. Keeping share pollId of share(' . $this->getSessionStoredShareToken() . '): ' . $this->getShare()->getPollId());
		} else {
			$this->pollId = $pollId;
		}

		$this->loadPoll();
		$this->request($permission);

		return $this;
	}

	/**
	 * Set poll id and load poll
	 * @return $this
	 */
	public function setPoll(Poll $poll, string $permission = self::PERMISSION_POLL_VIEW): static {
		$this->pollId = $poll->getId();
		$this->poll = $poll;
		$this->noShare = false;
		$this->request($permission);

		return $this;
	}

	public function getPoll(): ?Poll {
		if ($this->getToken()) {
			// first verify working share
			$this->loadShare();
		}
		$this->loadPoll();
		return $this->poll;
	}

	public function getShare(): ?Share {
		try {
			$this->loadShare();
		} catch (ShareNotFoundException $e) {
			return null;
		}

		return $this->share;
	}

	/**
	 * load poll
	 * @throws NotFoundException Thrown if poll not found
	 */
	private function loadPoll(): void {
		if ($this->poll?->getId() === $this->pollId) {
			// poll is already cached and pollId matches cached poll's id
			return;
		}

		try {
			// otherwise load poll from db
			$this->poll = $this->pollMapper->find((int) $this->pollId);
			$this->noShare = false;
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Error loading poll with id ' . $this->pollId);
		}
	}

	/**
	 * load share from db by session token or rely on cached share
	 * If the share token has changed, the share gets loaded from the db,
	 * the poll will get invalidated (set to null)
	 * and the pollId will get set to the share's pollId
	 */
	private function loadShare(): void {
		if ($this->noShare) {
			throw new ShareNotFoundException('No token was set for ACL');
		}

		// no token in session, try to find a user, who matches
		if (!$this->getToken()) {
			if ($this->getCurrentUser()->getIsLoggedIn()) {
				// search for logged in user's share, load it and return
				try {
					$this->share = $this->shareMapper->findByPollAndUser($this->getPollId(), $this->getUserId());
				} catch (\Throwable $ex) {
					$this->noShare = true;
					throw $ex;
				}
				// store share in session for further validations
				// $this->session->set(AppConstants::SESSION_KEY_SHARE_TOKEN, $this->share->getToken());
				return;
			} else {
				$this->share = new Share();
				$this->noShare = true;
				// must fail, if no token is present and not logged in
				throw new ShareNotFoundException('No token was set for ACL');
			}
		}

		// if share is already cached, verify against session token
		if ($this->share?->getToken() === $this->getToken()) {
			return;
		}

		$this->share = $this->shareMapper->findByToken((string) $this->getToken());

		// ensure, poll and currentUser get reset
		$this->poll = null;
		$this->currentUser = null;

		// set the poll id based on the share
		$this->pollId = $this->share->getPollId();
	}

	/**
	 * loads the current user from the userMapper or returns the cached one
	 */
	private function getCurrentUser(): UserBase {
		if (!$this->currentUser) {
			$this->currentUser = $this->userMapper->getCurrentUser();
		}
		return $this->currentUser;
	}

	/**
	 * returns the current pollId; Either from share or from setPollId()
	 */
	public function getPollId(): int {
		if ($this->getToken()) {
			$this->pollId = $this->getShare()->getPollId();
		}
		if (!$this->getPoll()->getId()) {
			throw new InvalidPollIdException('No pollId set!');
		}
		return (int) $this->getPoll()->getId();
	}

	private function getSessionStoredShareToken(): ?string {
		return $this->session->get(AppConstants::SESSION_KEY_SHARE_TOKEN);
	}

	public function getTokenIsValid(): bool {
		return boolval($this->getShare()?->getToken());
	}

	public function getUserId(): string {
		return $this->getCurrentUser()->getId();
	}

	private function getDisplayName(): string {
		return $this->getCurrentUser()->getDisplayName();
	}

	public function getIsOwner(): bool {
		return ($this->getPoll()->getOwner() === $this->getUserId());
	}

	public function getIsAllowed(string $permission, ?string $userId = null, ?int $pollId = null): bool|null {
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
			self::PERMISSION_OPTION_DELETE => $this->getAllowDeleteOption($userId, $pollId),
			self::PERMISSION_COMMENT_ADD => $this->getAllowComment(),
			self::PERMISSION_COMMENT_DELETE => $this->getAllowDeleteComment($userId, $pollId),
			self::PERMISSION_VOTE_EDIT => $this->getAllowVote(),
			default => false,
		};
	}

	/**
	 * request a permission level
	 * @throws ForbiddenException Thrown if access is denied
	 */
	public function request(string $permission, ?string $userId = null, ?int $pollId = null): void {
		if (!$this->getIsAllowed($permission, $userId, $pollId)) {
			throw new ForbiddenException('denied permission ' . $permission);
		}
	}

	/**
	 * getIsLoggedIn - Shortcut for UserMapper::getCurrentUser()->getId()
	 */
	public function getIsLoggedIn(): bool {
		return $this->getCurrentUser()->getIsLoggedIn();
	}

	/**
	 * getIsAdmin - Is the user a site admin
	 * Returns true, if user is in admin group
	 * Shortcut for UserMapper::getCurrentUser()->getIsAdmin()
	 */
	private function getIsAdmin(): bool {
		return $this->getCurrentUser()->getIsAdmin();
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
		return $this->getPoll()->getCurrentUserCountVotes() > 0;
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

		return count(
			array_filter($this->shareMapper->findByPoll($this->getPollId()), function ($item) {
				return ($item->getType() === Share::TYPE_GROUP && $this->getCurrentUser()->getIsInGroup($item->getUserId()));
			})
		) > 0;
	}

	/**
	 * getIsPersonallyInvited - Is the poll shared via user share?
	 * Returns true, if the current poll contains a user share for the current user.
	 * This only affects logged in users.
	 */
	private function getIsPersonallyInvited(): bool {
		if ($this->getIsLoggedIn() && $this->getShare()) {
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

	private function getIsDelegatedAdmin(): bool {
		if ($this->getShare()) {
			return $this->getShare()->getType() === Share::TYPE_ADMIN && !$this->share->getLocked();
		};
		return false;
	}

	private function getHasEmail(): bool {
		return boolVal($this->getCurrentUser()->getEmailAddress());
	}

	/**
	 * The detailed checks - For the sake of readability, the queries and selections
	 * were kept detailed and with less complexity
	 */


	/**
	 * Checks, if user is allowed to edit the poll configuration
	 **/
	private function getAllowEditPoll(): bool {
		if (defined('OC_CONSOLE')) {
			// Console god mode
			return true;
		}

		if ($this->getIsOwner()) {
			// owner is always allowed to edit the poll configuration
			return true;
		}

		if ($this->getIsDelegatedAdmin()) {
			// user has delegated owner rights
			return true;
		}

		// deny edit rights in all other cases
		return false;
	}

	/**
	 * Checks, if user is allowed to access poll
	 **/
	private function getAllowAccessPoll(): bool {
		if ($this->getAllowEditPoll()) {
			// edit rights include access to poll
			return true;
		}

		if ($this->getPoll()->getDeleted()) {
			// No further access to poll, if it is deleted
			return false;
		}

		if ($this->getIsInvolved()) {
			// grant access if user is involved in poll in any way
			return true;
		}

		if ($this->getPoll()->getAccess() === Poll::ACCESS_OPEN && $this->getIsLoggedIn()) {
			// grant access if poll poll is an open poll (for logged in users)
			return true;
		}

		// return check result of an existing valid share for this user
		return $this->getTokenIsValid();
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
		return $this->getIsAdmin();
	}

	/**
	 * Checks, if user is allowed to add add vote options
	 **/
	private function getAllowAddOptions(): bool {
		if ($this->getAllowEditPoll()) {
			// Edit right includes adding new options
			return true;
		}

		if (!$this->getAllowAccessPoll()) {
			// deny, if user has no access right to this poll
			return false;
		}

		if ($this->getShare()?->getType() === Share::TYPE_PUBLIC) {
			// public shares are not allowed to add options
			return false;
		}

		if ($this->getPoll()->getProposalsExpired()) {
			// Request for option proposals is expired, deny
			return false;
		}

		if ($this->getShare()?->getLocked()) {
			// Request for option proposals is expired, deny
			return false;
		}

		// Allow, if poll requests proposals
		return $this->getPoll()->getAllowProposals() === Poll::PROPOSAL_ALLOW;
	}


	/**
	 * @return bool|null
	 */
	private function getAllowDeleteOption(?string $optionOwner, ?int $pollId) {

		if (!$pollId) {
			$this->logger->warning('Poll id missing');
			return false;
		}

		$this->setPollId($pollId);

		if ($this->getAllowEditPoll()) {
			// Edit right includes deleting options
			return true;
		}

		if (!$optionOwner) {
			$this->logger->warning('Option owner missing');
			return false;
		}


		if ($this->matchUser($optionOwner)) {
			return true;
		};
	}

	/**
	 * Compare $userId with current user's id
	 */
	private function matchUser(string $userId): bool {
		return $this->getCurrentUser()->getId() === $userId;
	}

	/**
	 * Checks, if user is allowed to see and write comments
	 **/
	private function getAllowComment(): bool {
		if (!$this->getAllowAccessPoll()) {
			// user has no access right to this poll
			return false;
		}

		if ($this->getShare()?->getType() === Share::TYPE_PUBLIC) {
			// public shares are not allowed to comment
			return false;
		}

		if ($this->getShare()?->getLocked()) {
			// public shares are not allowed to comment
			return false;
		}

		return (bool) $this->getPoll()->getAllowComment();
	}

	private function getAllowDeleteComment(?string $commentOwner, ?int $pollId): bool {
		if (!$pollId || !$commentOwner) {
			throw new ForbiddenException('Comment owner or poll id missing');
		}

		$this->setPollId($pollId);

		// Poll owner is allowed to delete every comment
		return $this->getIsOwner() || $this->matchUser($commentOwner);
	}
	/**
	 * Checks, if user is allowed to comment
	 **/
	private function getAllowVote(): bool {
		if (!$this->getAllowAccessPoll()) {
			// user has no access right to this poll
			return false;
		}

		if ($this->getShare()?->getType() === Share::TYPE_PUBLIC) {
			// public shares are not allowed to vote
			return false;
		}

		if ($this->getShare()?->getLocked()) {
			// public shares are not allowed to vote
			return false;
		}

		// deny votes, if poll is expired
		return !$this->getPoll()->getExpired();
	}

	private function getAllowSubscribeToPoll(): bool {
		if (!$this->getAllowAccessPoll()) {
			// user has no access right to this poll
			return false;
		}

		return $this->getHasEmail();
	}

	private function getShowResults(): bool {
		if ($this->getAllowEditPoll()) {
			// edit rights include access to results
			return true;
		}

		if (!$this->getAllowAccessPoll()) {
			// no access to poll, deny
			return false;
		}

		if ($this->getPoll()->getShowResults() === Poll::SHOW_RESULTS_CLOSED && $this->getPoll()->getExpired()) {
			// show results, when poll is cloed
			return true;
		}

		return $this->getPoll()->getShowResults() === Poll::SHOW_RESULTS_ALWAYS;
	}
}
