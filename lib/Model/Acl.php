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
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Exceptions\ForbiddenException;
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
	public const PERMISSION_COMMENT_ADD = 'comment';
	public const PERMISSION_OPTIONS_ADD = 'add_options';
	public const PERMISSION_VOTE_EDIT = 'vote';
	public const PERMISSION_PUBLIC_SHARES = 'publicShares';
	public const PERMISSION_ALL_ACCESS = 'allAccess';
	private ?string $token;
	private ?int $pollId;


	public function __construct(
		private AppSettings $appSettings,
		private LoggerInterface $logger,
		private PollMapper $pollMapper,
		private ISession $session,
		private ShareMapper $shareMapper,
		private UserMapper $userMapper,
		private VoteMapper $voteMapper,
		private ?Poll $poll,
		private ?Share $share,
	) {
		$this->pollId = null;
		$this->poll = null;
		$this->share = null;
		$this->appSettings = new AppSettings;
	}

	public function jsonSerialize(): array {
		return	[
			'pollId' => $this->getPoll()->getId(),
			'pollExpired' => $this->getPoll()->getExpired(),
			'pollExpire' => $this->getPoll()->getExpire(),
			'token' => $this->getShare()?->getToken(),
			'currentUser' => [
				'displayName' => $this->getDisplayName(),
				'hasVoted' => $this->getIsParticipant(),
				'isInvolved' => $this->getIsInvolved(),
				'isLoggedIn' => $this->getIsLoggedIn(),
				'isNoUser' => !$this->getIsLoggedIn(),
				'isOwner' => $this->getIsOwner(),
				'userId' => $this->getUserId(),
			],
			'permissions' => [
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
			]
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
		$this->session->set(AppConstants::SESSION_KEY_SHARE_TOKEN, $token);
		if ($token !== $this->getToken()) {
			$this->logger->warning('SessionToken (' . $this->getToken() .') differs from paramater token (' . $token .'). Using parameter token for further checking inside this Acl');
		}
		$this->setPollId($this->getShare()->getPollId(), $permission);

		return $this;
	}

	/**
	 * Set poll id and load poll
	 */
	public function setPollId(int $pollId = 0, string $permission = self::PERMISSION_POLL_VIEW): Acl {
		$this->pollId = $pollId;
		$this->request($permission);													// just check the permissions in all cases
		return $this;
	}

	public function getPoll(): ?Poll {
		try {
			if (!$this->poll) {
				$this->poll = $this->pollMapper->find((int) $this->pollId);
			}
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Error loading poll with id ' . $this->pollId);
		}
		return $this->poll;
	}

	public function getShare(): ?Share {
		try {
			if (!$this->share) {
				// First try loading share from token
				$this->share = $this->shareMapper->findByToken((string) $this->getToken());
			}
		} catch (ShareNotFoundException $e) {
			try {
				// if no loading by token was possible, load share by poll Id and current user's user id
				$this->share = $this->shareMapper->findByPollAndUser($this->getPollId(), $this->getUserId());
			} catch (ShareNotFoundException $e) {
				// no share found, return null
				return null;
			}
		}
		return $this->share;
	}

	private function getCurrentUser(): ?UserBase {
		return $this->userMapper->getCurrentUser();
	}

	public function getPollId(): int {
		return (int) $this->pollId;
	}

	public function getToken(): ?string {
		$this->token = $this->session->get(AppConstants::SESSION_KEY_SHARE_TOKEN);
		return $this->token;
	}

	public function getTokenIsValid(): bool {
		return boolval($this->getShare()?->getToken());
	}

	public function getUserId(): string {
		return (string) $this->getCurrentUser()?->getId();
	}

	private function getDisplayName(): string {
		return (string) $this->getCurrentUser()?->getDisplayName();
	}

	/**
	 * Validations
	 */

	public function getIsOwner(): bool {
		return ($this->getPoll()->getOwner() === $this->getUserId());
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

	/**
	 * getIsLogged - Is user logged in to nextcloud?
	 */
	public function getIsLoggedIn(): bool {
		return (bool) $this->getCurrentUser()?->getIsLoggedIn();
	}

	/**
	 * getIsAdmin - Is the user admin
	 * Returns true, if user is in admin group
	 */
	private function getIsAdmin(): bool {
		return (bool) $this->getCurrentUser()?->getIsAdmin();
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
				return ($item->getType() === Share::TYPE_GROUP && $this->getCurrentUser()->getIsInGroup($item->getUserId()));
			})
		);
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
		if ($this->getShare()) {							// load share, if not loaded
			return $this->getShare()->getType() === Share::TYPE_ADMIN && !$this->share->getLocked();
		};
		return false;
	}

	private function getIsShareValidForGuests(): bool {
		return in_array($this->getShare()->getType(), Share::SHARE_PUBLIC_ACCESS_ALLOWED);
	}

	private function getIsShareValidForUsers(): bool {
		return in_array($this->getShare()->getType(), Share::SHARE_AUTH_ACCESS_ALLOWED);
	}

	private function getHasEmail(): bool {
		return (bool) $this->getCurrentUser()?->getEmailAddress();
	}

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
