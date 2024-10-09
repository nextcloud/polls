<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use JsonSerializable;
use OCA\Polls\AppConstants;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\NoDeadLineException;
use OCA\Polls\Helper\Container;
use OCA\Polls\UserSession;
use OCP\IURLGenerator;

/**
 * @psalm-api
 * @method int getId()
 * @method void setId(int $value)
 * @method string getType()
 * @method void setType(string $value)
 * @method string getTitle()
 * @method void setTitle(string $value)
 * @method string getDescription()
 * @method void setDescription(string $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method int getCreated()
 * @method void setCreated(int $value)
 * @method int getExpire()
 * @method void setExpire(int $value)
 * @method int getDeleted()
 * @method void setDeleted(int $value)
 * @method string getAccess()
 * @method void setAccess(string $value)
 * @method int getAnonymous()
 * @method void setAnonymous(int $value)
 * @method int getAllowComment()
 * @method void setAllowComment(int $value)
 * @method int getAllowMaybe()
 * @method void setAllowMaybe(int $value)
 * @method string getAllowProposals()
 * @method void setAllowProposals(string $value)
 * @method int getProposalsExpire()
 * @method void setProposalsExpire(int $value)
 * @method int getVoteLimit()
 * @method void setVoteLimit(int $value)
 * @method int getOptionLimit()
 * @method void setOptionLimit(int $value)
 * @method string getShowResults()
 * @method void setShowResults(string $value)
 * @method int getAdminAccess()
 * @method void setAdminAccess(int $value)
 * @method int getHideBookedUp()
 * @method void setHideBookedUp(int $value)
 * @method int getUseNo()
 * @method void setUseNo(int $value)
 * @method int getLastInteraction()
 * @method void setLastInteraction(int $value)
 * @method string getMiscSettings()
 * @method void setMiscSettings(string $value)
 *
 * Magic functions for joined columns
 * @method int getMinDate()
 * @method int getMaxDate()
 * @method int getShareToken()
 * @method int getCountOptions()
 *
 * Magic functions for subqueried columns
 * @method int getCurrentUserOrphanedVotes()
 * @method int getCurrentUserVotes()
 * @method int getCurrentUserVotesYes()
 * @method int getParticipantsCount()
 */

class Poll extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_polls';
	public const TYPE_DATE = 'datePoll';
	public const TYPE_TEXT = 'textPoll';
	public const ACCESS_HIDDEN = 'hidden';
	public const ACCESS_PUBLIC = 'public';
	public const ACCESS_PRIVATE = 'private';
	public const ACCESS_OPEN = 'open';
	public const SHOW_RESULTS_ALWAYS = 'always';
	public const SHOW_RESULTS_CLOSED = 'closed';
	public const SHOW_RESULTS_NEVER = 'never';
	public const PROPOSAL_DISALLOW = 'disallow';
	public const PROPOSAL_ALLOW = 'allow';
	public const PROPOSAL_REVIEW = 'review';
	public const URI_PREFIX = 'poll/';
	public const FIVE_DAYS = 432000;
	public const FOUR_DAYS = 345600;
	public const THREE_DAYS = 259200;
	public const TWO_DAYS = 172800;
	public const ONE_AND_HALF_DAY = 129600;

	public const ROLE_USER = Share::TYPE_USER;
	public const ROLE_ADMIN = Share::TYPE_ADMIN;
	public const ROLE_EMAIL = Share::TYPE_EMAIL;
	public const ROLE_CONTACT = Share::TYPE_CONTACT;
	public const ROLE_EXTERNAL = Share::TYPE_EXTERNAL;
	public const ROLE_OWNER = 'owner';
	public const ROLE_NONE = 'none';

	public const PERMISSION_OVERRIDE = 'override_permission';
	public const PERMISSION_POLL_VIEW = 'view';
	public const PERMISSION_POLL_EDIT = 'edit';
	public const PERMISSION_POLL_DELETE = 'delete';
	public const PERMISSION_POLL_ARCHIVE = 'archive';
	public const PERMISSION_POLL_RESULTS_VIEW = 'seeResults';
	public const PERMISSION_POLL_USERNAMES_VIEW = 'seeUserNames';
	public const PERMISSION_POLL_TAKEOVER = 'takeOver';
	public const PERMISSION_POLL_SUBSCRIBE = 'subscribe';
	public const PERMISSION_COMMENT_ADD = 'addComment';
	public const PERMISSION_COMMENT_DELETE = 'deleteComment';
	public const PERMISSION_OPTIONS_ADD = 'addOptions';
	public const PERMISSION_OPTION_DELETE = 'deleteOption';
	public const PERMISSION_VOTE_EDIT = 'vote';


	private IURLGenerator $urlGenerator;
	protected UserSession $userSession;

	// schema columns
	public $id = null;
	protected string $type = '';
	protected string $title = '';
	protected ?string $description = '';
	protected ?string $owner = '';
	protected int $created = 0;
	protected int $expire = 0;
	protected int $deleted = 0;
	protected string $access = '';
	protected int $anonymous = 0;
	protected int $allowMaybe = 0;
	protected string $allowProposals = '';
	protected int $proposalsExpire = 0;
	protected int $voteLimit = 0;
	protected int $optionLimit = 0;
	protected string $showResults = '';
	protected int $adminAccess = 0;
	protected int $allowComment = 0;
	protected int $hideBookedUp = 0;
	protected int $useNo = 0;
	protected int $lastInteraction = 0;
	protected ?string $miscSettings = '';

	// joined columns
	protected ?int $isCurrentUserLocked = 0;
	protected int $maxDate = 0;
	protected int $minDate = 0;
	protected string $userRole = self::ROLE_NONE;
	protected string $shareToken = '';
	protected ?string $groupShares = '';
	protected int $countOptions = 0;
	
	// subqueried columns
	protected int $currentUserOrphanedVotes = 0;
	protected int $currentUserVotes = 0;
	protected int $currentUserVotesYes = 0;
	protected int $participantsCount = 0;

	public function __construct() {
		$this->addType('created', 'int');
		$this->addType('expire', 'int');
		$this->addType('deleted', 'int');
		$this->addType('anonymous', 'int');
		$this->addType('allowComment', 'int');
		$this->addType('allowMaybe', 'int');
		$this->addType('proposalsExpire', 'int');
		$this->addType('voteLimit', 'int');
		$this->addType('optionLimit', 'int');
		$this->addType('adminAccess', 'int');
		$this->addType('hideBookedUp', 'int');
		$this->addType('useNo', 'int');
		$this->addType('lastInteraction', 'int');
		
		// joined columns
		$this->addType('isCurrentUserLocked', 'int');
		$this->addType('maxDate', 'int');
		$this->addType('minDate', 'int');
		$this->addType('countOptions', 'int');

		// subqueried columns
		$this->addType('currentUserVotes', 'int');
		$this->addType('currentUserVotesYes', 'int');
		$this->addType('currentUserOrphanedVotes', 'int');
		$this->addType('participantsCount', 'int');

		$this->urlGenerator = Container::queryClass(IURLGenerator::class);
		$this->userSession = Container::queryClass(UserSession::class);
	}

	/**
	 * @return array
	 *
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'type' => $this->getType(),
			// editable settings
			'configuration' => $this->getConfigurationArray(),
			// read only properties
			'descriptionSafe' => $this->getDescriptionSafe(),
			'owner' => $this->getUser(),
			'status' => $this->getStatusArray(),
			'currentUserStatus' => $this->getCurrentUserStatus(),
			'permissions' => $this->getPermissionsArray(),
		];
	}

	public function getStatusArray(): array {
		return [
			'lastInteraction' => $this->getLastInteraction(),
			'created' => $this->getCreated(),
			'deleted' => boolval($this->getDeleted()),
			'expired' => $this->getExpired(),
			'relevantThreshold' => $this->getRelevantThreshold(),
			'countOptions' => $this->getCountOptions(),
			'countParticipants' => $this->getIsAllowed(self::PERMISSION_POLL_RESULTS_VIEW) ? $this->getParticipantsCount() : 0,
		];
	}
	public function getConfigurationArray(): array {
		return [
			'title' => $this->getTitle(),
			'description' => $this->getDescription(),
			'access' => $this->getAccess(),
			'allowComment' => boolval($this->getAllowComment()),
			'allowMaybe' => boolval($this->getAllowMaybe()),
			'allowProposals' => $this->getAllowProposals(),
			'anonymous' => boolval($this->getAnonymous()),
			'autoReminder' => $this->getAutoReminder(),
			'expire' => $this->getExpire(),
			'hideBookedUp' => boolval($this->getHideBookedUp()),
			'proposalsExpire' => $this->getProposalsExpire(),
			'showResults' => $this->getShowResults(),
			'useNo' => boolval($this->getUseNo()),
			'maxVotesPerOption' => $this->getOptionLimit(),
			'maxVotesPerUser' => $this->getVoteLimit(),
		];
	}

	public function getCurrentUserStatus(): array {
		return [
			'userRole' => $this->getUserRole(),
			'isLocked' => boolval($this->getIsCurrentUserLocked()),
			'isInvolved' => $this->getIsInvolved(),
			'isLoggedIn' => $this->userSession->getIsLoggedIn(),
			'isNoUser' => !$this->userSession->getIsLoggedIn(),
			'isOwner' => $this->getIsPollOwner(),
			'userId' => $this->getUserId(),
			'orphanedVotes' => $this->getCurrentUserOrphanedVotes(),
			'yesVotes' => $this->getCurrentUserVotesYes(),
			'countVotes' => $this->getCurrentUserVotes(),
			'shareToken' => $this->getShareToken(),
			'groupInvitations' => $this->getGroupShares(),
		];
	}
	public function getPermissionsArray(): array {
		return [
			'addOptions' => $this->getIsAllowed(self::PERMISSION_OPTIONS_ADD),
			'archive' => $this->getIsAllowed(self::PERMISSION_POLL_ARCHIVE),
			'comment' => $this->getIsAllowed(self::PERMISSION_COMMENT_ADD),
			'delete' => $this->getIsAllowed(self::PERMISSION_POLL_DELETE),
			'edit' => $this->getIsAllowed(self::PERMISSION_POLL_EDIT),
			'seeResults' => $this->getIsAllowed(self::PERMISSION_POLL_RESULTS_VIEW),
			'seeUsernames' => $this->getIsAllowed(self::PERMISSION_POLL_USERNAMES_VIEW),
			'subscribe' => $this->getIsAllowed(self::PERMISSION_POLL_SUBSCRIBE),
			'view' => $this->getIsAllowed(self::PERMISSION_POLL_VIEW),
			'vote' => $this->getIsAllowed(self::PERMISSION_VOTE_EDIT),
		];
	}


	/**
	 * @return static
	 */
	public function deserializeArray(array $pollConfiguration): self {
		$this->setTitle($pollConfiguration['title'] ?? $this->getTitle());
		$this->setDescription($pollConfiguration['description'] ?? $this->getDescription());
		$this->setAccess($pollConfiguration['access'] ?? $this->getAccess());
		$this->setAllowComment($pollConfiguration['allowComment'] ?? $this->getAllowComment());
		$this->setAllowMaybe($pollConfiguration['allowMaybe'] ?? $this->getAllowMaybe());
		$this->setAllowProposals($pollConfiguration['allowProposals'] ?? $this->getAllowProposals());
		$this->setAnonymous($pollConfiguration['anonymous'] ?? $this->getAnonymous());
		$this->setAutoReminder($pollConfiguration['autoReminder'] ?? $this->getAutoReminder());
		$this->setExpire($pollConfiguration['expire'] ?? $this->getExpire());
		$this->setHideBookedUp($pollConfiguration['hideBookedUp'] ?? $this->getHideBookedUp());
		$this->setProposalsExpire($pollConfiguration['proposalsExpire'] ?? $this->getProposalsExpire());
		$this->setShowResults($pollConfiguration['showResults'] ?? $this->getShowResults());
		$this->setUseNo($pollConfiguration['useNo'] ?? $this->getUseNo());
		$this->setOptionLimit($pollConfiguration['maxVotesPerOption'] ?? $this->getOptionLimit());
		$this->setVoteLimit($pollConfiguration['maxVotesPerUser'] ?? $this->getVoteLimit());
		return $this;
	}

	public function getExpired(): bool {
		$compareTime = time();
		$expiry = $this->getExpire();

		return (
			$expiry > 0
			&& $expiry < $compareTime
		);
	}

	public function getUserRole(): string {
		if ($this->userSession->getCurrentUserId() === $this->getOwner()) {
			return self::ROLE_OWNER;
		}
		if ($this->getIsCurrentUserLocked() && $this->userRole === self::ROLE_ADMIN) {
			return self::ROLE_USER;
		}

		if ($this->userSession->getShareType() === Share::TYPE_PUBLIC) {
			return Share::TYPE_PUBLIC;
		}

		return $this->userRole;
	}
	
	public function getVoteUrl(): string {
		return $this->urlGenerator->linkToRouteAbsolute(
			AppConstants::APP_ID . '.page.vote',
			['id' => $this->getId()]
		);
	}

	public function setAutoReminder(bool|int $value): void {
		$this->setMiscSettingsByKey('autoReminder', (bool)$value);
	}

	public function getAutoReminder(): bool {
		return $this->getMiscSettingsArray()['autoReminder'] ?? false;
	}

	// alias of getId()
	public function getPollId(): int {
		return $this->getId();
	}

	// alias of getOwner()
	public function getUserId(): string {
		return $this->getOwner();
	}

	// alias of setOwner($value)
	public function setUserId(string $userId): void {
		$this->setOwner($userId);
	}

	public function getGroupShares(): array {
		if ($this->groupShares !== null && $this->groupShares !== '') {
			// explode with separator and remove empty elements
			return array_filter(explode(PollMapper::CONCAT_SEPARATOR, PollMapper::CONCAT_SEPARATOR . $this->groupShares));
		}

		return [];
	}

	public function getAccess() {
		if ($this->access === self::ACCESS_PUBLIC) {
			return self::ACCESS_OPEN;
		}
		if ($this->access === self::ACCESS_HIDDEN) {
			return self::ACCESS_PRIVATE;
		}
		return $this->access;
	}

	public function getProposalsExpired(): bool {
		return (
			$this->getProposalsExpire() > 0
			&& $this->getProposalsExpire() < time()
		);
	}

	public function getPollShowResults() {
		// avoiding migration, expired has been renamed to closed
		return $this->showResults === 'expired' ? Poll::SHOW_RESULTS_CLOSED : $this->showResults;
	}

	public function getDescription(): string {
		return $this->description ?? '';
	}

	public function getDescriptionSafe(): string {
		return htmlspecialchars($this->getDescription());
	}


	private function setMiscSettingsArray(array $value): void {
		$this->setMiscSettings(json_encode($value));
	}

	private function getMiscSettingsArray(): array {
		if ($this->getMiscSettings()) {
			return json_decode($this->getMiscSettings(), true);
		}

		return [];
	}

	public function getTimeToDeadline(int $time = 0): int {
		if ($time === 0) {
			$time = time();
		}

		$deadline = $this->getDeadline();

		if (
			$deadline - $this->getCreated() > self::FIVE_DAYS
			&& $deadline - $time < self::TWO_DAYS
			&& $deadline > $time
		) {
			return self::TWO_DAYS;
		}

		if (
			$deadline - $this->getCreated() > self::TWO_DAYS
			&& $deadline - $time < self::ONE_AND_HALF_DAY
			&& $deadline > $time
		) {
			return self::ONE_AND_HALF_DAY;
		}
		throw new NoDeadLineException();
	}

	public function getRelevantThreshold(): int {
		return max(
			$this->getCreated(),
			$this->getLastInteraction(),
			$this->getExpire(),
			$this->getMaxDate(),
		);
	}

	public function getIsCurrentUserLocked(): bool {
		return boolval($this->isCurrentUserLocked);
	}

	public function getDeadline(): int {
		// if expiration is set return expiration date
		if ($this->getExpire()) {
			return $this->getExpire();
		}

		if ($this->getType() === Poll::TYPE_DATE) {
			// use lowest date option as reminder deadline threshold
			// if no options are set return is the current time
			return $this->getMinDate();
		}
		throw new NoDeadLineException();
	}

	/**
	 * @param bool|string|int|array $value
	 */
	private function setMiscSettingsByKey(string $key, $value): void {
		$miscSettings = $this->getMiscSettingsArray();
		$miscSettings[$key] = $value;
		$this->setMiscSettingsArray($miscSettings);
	}

	/**
	 *
	 * Check Permissions
	 *
	 */

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
	 * Check particular rights and inform via boolean value, if the right is granted or denied
	 */
	public function getIsAllowed(string $permission): bool {
		return match ($permission) {
			self::PERMISSION_OVERRIDE => true,
			self::PERMISSION_POLL_VIEW => $this->getAllowAccessPoll(),
			self::PERMISSION_POLL_EDIT => $this->getAllowEditPoll(),
			self::PERMISSION_POLL_DELETE => $this->getAllowDeletePoll(),
			self::PERMISSION_POLL_ARCHIVE => $this->getAllowEditPoll(),
			self::PERMISSION_POLL_TAKEOVER => $this->getAllowEditPoll(),
			self::PERMISSION_POLL_SUBSCRIBE => $this->getAllowSubscribeToPoll(),
			self::PERMISSION_POLL_RESULTS_VIEW => $this->getAllowShowResults(),
			self::PERMISSION_POLL_USERNAMES_VIEW => $this->getAllowEditPoll() || !$this->getAnonymous(),
			self::PERMISSION_OPTIONS_ADD => $this->getAllowAddOptions(),
			self::PERMISSION_OPTION_DELETE => $this->getAllowDeleteOption(),
			self::PERMISSION_COMMENT_ADD => $this->getAllowCommenting(),
			self::PERMISSION_COMMENT_DELETE => $this->getAllowDeleteComment(),
			self::PERMISSION_VOTE_EDIT => $this->getAllowVote(),
			default => false,
		};
	}

	/**
	 * getIsInvolved - Is current user involved in current poll?
	 * @return bool Returns true, if the current user is involved in the poll via share, as a participant or as the poll owner.
	 */
	private function getIsInvolved(): bool {
		return (
			$this->getIsPollOwner()
			|| $this->getIsParticipant()
			|| $this->getIsPersonallyInvited())
			|| $this->getIsInvitedViaGroupShare();
	}

	/**
	 * Check, if poll settings is set to open access for internal users
	 */
	private function getIsOpenPoll(): bool {
		return $this->getAccess() === Poll::ACCESS_OPEN && $this->userSession->getIsLoggedIn();
	}

	/**
	 * getIsParticipant - Is user a participant?
	 * @return bool Returns true, if the current user is already a particitipant of the current poll.
	 */
	private function getIsParticipant(): bool {
		return $this->getCurrentUserVotes() > 0;
	}

	/**
	 * getIsInvitedViaGroupShare - Is the poll shared via group share?
	 * where the current user is member of. This only affects logged in users.
	 * @return bool Returns true, if the current poll contains a group share with a group,
	 */
	private function getIsInvitedViaGroupShare(): bool {
		if (!$this->userSession->getIsLoggedIn()) {
			return false;
		}

		return count($this->getGroupSharesForUser()) > 0;
	}

	private function getGroupSharesForUser(): array {
		return array_filter($this->getGroupShares(), function ($groupName) {
			return ($this->userSession->getUser()->getIsInGroup($groupName));
		});
	}
	/**
	 * getIsPersonallyInvited - Is the poll shared via user share with the current user?
	 * Checking via user role
	 * @return bool Returns true, if the current poll contains a user role which matches a share type
	 */
	private function getIsPersonallyInvited(): bool {
		return in_array($this->getUserRole(), [
			Poll::ROLE_ADMIN,
			Poll::ROLE_USER,
			Poll::ROLE_EXTERNAL,
			Poll::ROLE_EMAIL,
			Poll::ROLE_CONTACT,
		]);
	}

	/**
	 * The detailed checks - For the sake of readability, the queries and selections
	 * were kept detailed and with low complexity
	 */
	
	/**
	 * Checks, if the user has delegated admin rights to edit poll settings via share
	 */
	private function getIsDelegatedAdmin(): bool {
		return $this->getUserRole() === Poll::ROLE_ADMIN
			&& !$this->getIsCurrentUserLocked();
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
	 * Checks, if user is allowed to access (view) poll
	 */
	private function getAllowAccessPoll(): bool {
		// edit rights include access to poll
		if ($this->getAllowEditPoll()) {
			return true;
		}

		// No further access to poll, if it is deleted
		if ($this->getDeleted()) {
			return false;
		}

		// grant access if poll poll is an open poll (for logged in users)
		if ($this->getIsOpenPoll() && $this->userSession->getIsLoggedIn()) {
			return true;
		}

		// grant access if user is involved in poll in any way
		if ($this->getIsInvolved()) {
			return true;
		}
		$share = $this->userSession->getShare();
		// return check result of an existing valid share for this user
		return boolval($share->getId() && $share->getPollId() === $this->getId());
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

		// additionally site admins are allowed to delete polls, in all other cases deny poll deletion right
		return $this->userSession->getUser()->getIsAdmin();
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
		if ($this->userSession->getShare()->getType() === Share::TYPE_PUBLIC) {
			return false;
		}

		// Request for option proposals is expired, deny
		if ($this->getProposalsExpired()) {
			return false;
		}

		// Locked Users are not allowed to add options
		if (boolval($this->getIsCurrentUserLocked())) {
			return false;
		}

		// Allow, if poll requests proposals
		return $this->getAllowProposals() === Poll::PROPOSAL_ALLOW;
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
		return (bool)$this->userSession->getUser()->getId() && $this->userSession->getUser()->getId() === $userId;
	}

	/**
	 * Checks, if the current user is the poll owner
	 **/
	public function getIsPollOwner(): bool {
		return ($this->getUserRole() === Poll::ROLE_OWNER);
	}


	/**
	 * Permission checks
	 */

	/**
	 * Checks, if user is allowed to see and write comments
	 **/
	private function getAllowCommenting(): bool {
		// user has no access right to this poll
		if (!$this->getAllowAccessPoll()) {
			return false;
		}

		// public shares are not allowed to comment
		if ($this->userSession->getShare()->getType() === Share::TYPE_PUBLIC) {
			return false;
		}

		// public shares are not allowed to comment
		if (boolval($this->getIsCurrentUserLocked())) {
			return false;
		}

		// return the poll setting for comments
		return (bool)$this->getAllowComment();
	}

	/**
	 * Checks, if user is allowed to delete comments from poll
	 **/
	private function getAllowDeleteComment(): bool {
		return $this->getAllowEditPoll();
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
		if ($this->userSession->getShare()->getType() === Share::TYPE_PUBLIC) {
			return false;
		}

		// Locked users are not allowed to vote
		if (boolval($this->getIsCurrentUserLocked())) {
			return false;
		}

		// deny votes, if poll is expired
		return !$this->getExpired();
	}

	/**
	 * Checks, if user is allowed to subscribe to updates
	 **/
	private function getAllowSubscribeToPoll(): bool {
		// user with access to poll are always allowed to subscribe
		if (!$this->getAllowAccessPoll()) {
			return false;
		}

		return $this->userSession->getUser()->getHasEmail();
	}

	/**
	 * Checks, if user is allowed to see results of current poll
	 **/
	private function getAllowShowResults(): bool {
		// edit rights include access to results
		if ($this->getAllowEditPoll()) {
			return true;
		}

		// no access to poll, deny
		if (!$this->getAllowAccessPoll()) {
			return false;
		}
		
		// show results, when poll is closed
		if ($this->getShowResults() === Poll::SHOW_RESULTS_CLOSED && $this->getExpired()) {
			return true;
		}
		// return poll settings
		return $this->getShowResults() === Poll::SHOW_RESULTS_ALWAYS;
	}


}
