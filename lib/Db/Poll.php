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
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Model\Settings\SystemSettings;
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
 * @method void setDescription(string $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method int getCreated()
 * @method void setCreated(int $value)
 * @method int getExpire()
 * @method void setExpire(int $value)
 * @method int getDeleted()
 * @method void setDeleted(int $value)
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
 * @method string getVotingVariant()
 * @method void setVotingVariant(string $value)
 *
 * Magic functions for joined columns
 * @method int getMinDate()
 * @method int getMaxDate()
 * @method int getShareToken()
 * @method int getOptionsCount()
 * @method int getProposalsCount()
 * @method int getProposalsCount()
 * @method int getCurrentUserVotes()
 * @method int getCurrentUserVotesYes()
 * @method int getCurrentUserVotesNo()
 * @method int getCurrentUserVotesMaybe()
 * @method int getParticipantsCount()
 *
 * Magic functions for subqueried columns
 * @method int getCurrentUserOrphanedVotes()
 */

class Poll extends EntityWithUser implements JsonSerializable {
	public const TABLE = 'polls_polls';
	public const TYPE_DATE = 'datePoll';
	public const TYPE_TEXT = 'textPoll';
	public const VARIANT_SIMPLE = 'simple';
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
	public const PERMISSION_POLL_CHANGE_OWNER = 'changeOwner';
	public const PERMISSION_POLL_DELETE = 'delete';
	public const PERMISSION_POLL_ARCHIVE = 'archive';
	public const PERMISSION_POLL_RESULTS_VIEW = 'seeResults';
	public const PERMISSION_POLL_USERNAMES_VIEW = 'seeUserNames';
	public const PERMISSION_POLL_TAKEOVER = 'takeOver';
	public const PERMISSION_POLL_SUBSCRIBE = 'subscribe';
	public const PERMISSION_COMMENT_ADD = 'addComment';
	public const PERMISSION_COMMENT_DELETE = 'deleteComment';
	public const PERMISSION_OPTION_ADD = 'addOptions';
	public const PERMISSION_OPTION_CONFIRM = 'confirmOption';
	public const PERMISSION_OPTION_CLONE = 'cloneOption';
	public const PERMISSION_OPTION_DELETE = 'deleteOption';
	public const PERMISSION_OPTIONS_REORDER = 'reorderOptions';
	public const PERMISSION_OPTIONS_SHIFT = 'shiftOptions';
	public const PERMISSION_VOTE_EDIT = 'vote';
	public const PERMISSION_VOTE_FOREIGN_CHANGE = 'changeForeignVotes';
	public const PERMISSION_SHARE_ADD = 'shareCreate';
	public const PERMISSION_SHARE_ADD_EXTERNAL = 'shareCreateExternal';
	public const PERMISSION_DEANONYMIZE = 'deanonymize';

	private IURLGenerator $urlGenerator;
	protected SystemSettings $systemSettings;
	protected AppSettings $appSettings;
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
	protected string $votingVariant = '';

	// joined columns
	protected ?int $isCurrentUserLocked = 0;
	protected int $maxDate = 0;
	protected int $minDate = 0;
	protected string $userRole = self::ROLE_NONE;
	protected string $shareToken = '';
	protected ?string $groupShares = '';
	protected int $optionsCount = 0;
	protected int $proposalsCount = 0;
	protected ?string $pollGroups = '';
	protected ?string $pollGroupUserShares = '';
	protected int $currentUserVotes = 0;
	protected int $currentUserVotesYes = 0;
	protected int $currentUserVotesNo = 0;
	protected int $currentUserVotesMaybe = 0;
	protected int $participantsCount = 0;

	// subqueried columns
	protected int $currentUserOrphanedVotes = 0;

	public function __construct() {
		$this->addType('created', 'integer');
		$this->addType('expire', 'integer');
		$this->addType('deleted', 'integer');
		$this->addType('anonymous', 'integer');
		$this->addType('allowComment', 'integer');
		$this->addType('allowMaybe', 'integer');
		$this->addType('proposalsExpire', 'integer');
		$this->addType('voteLimit', 'integer');
		$this->addType('optionLimit', 'integer');
		$this->addType('adminAccess', 'integer');
		$this->addType('hideBookedUp', 'integer');
		$this->addType('useNo', 'integer');
		$this->addType('lastInteraction', 'integer');

		// joined columns
		$this->addType('isCurrentUserLocked', 'integer');
		$this->addType('maxDate', 'integer');
		$this->addType('minDate', 'integer');
		$this->addType('countOptions', 'integer');
		$this->addType('currentUserVotes', 'integer');
		$this->addType('currentUserVotesYes', 'integer');
		$this->addType('currentUserVotesNo', 'integer');
		$this->addType('currentUserVotesMaybe', 'integer');

		// subqueried columns
		$this->addType('currentUserOrphanedVotes', 'integer');
		$this->addType('participantsCount', 'integer');

		$this->urlGenerator = Container::queryClass(IURLGenerator::class);
		$this->systemSettings = Container::queryClass(SystemSettings::class);
		$this->appSettings = Container::queryClass(AppSettings::class);
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
			'votingVariant' => $this->getVotingVariant(),
			// editable settings
			'configuration' => $this->getConfigurationArray(),
			// read only properties
			'descriptionSafe' => $this->getDescriptionSafe(),
			'owner' => $this->getUser(),
			'status' => $this->getStatusArray(),
			'currentUserStatus' => $this->getCurrentUserStatus(),
			'permissions' => $this->getPermissionsArray(),
			'pollGroups' => $this->getPollGroups(),
		];
	}

	public function getStatusArray(): array {
		return [
			'lastInteraction' => $this->getLastInteraction(),
			'created' => $this->getCreated(),
			'isAnonymous' => boolval($this->getAnonymous()),
			'isArchived' => boolval($this->getDeleted()),
			'isExpired' => $this->getExpired(),
			'isRealAnonymous' => $this->getAnonymous() < 0,
			'relevantThreshold' => $this->getRelevantThreshold(),
			'deletionDate' => $this->getDeletionDate(),
			'archivedDate' => $this->getDeleted(),
			'countOptions' => $this->getOptionsCount(),
			'countParticipants' => $this->getIsAllowed(self::PERMISSION_POLL_RESULTS_VIEW) ? $this->getParticipantsCount() : 0,
			'countProposals' => $this->getIsAllowed(self::PERMISSION_POLL_RESULTS_VIEW) ? $this->getProposalsCount() : 0,
		];
	}
	public function getConfigurationArray(): array {
		return [
			'access' => $this->getAccess(),
			'allowComment' => boolval($this->getAllowComment()),
			'allowMaybe' => boolval($this->getAllowMaybe()),
			'allowProposals' => $this->getAllowProposals(),
			'anonymous' => boolval($this->getAnonymous()),
			'autoReminder' => $this->getAutoReminder(),
			'collapseDescription' => $this->getCollapseDescription(),
			'description' => $this->getDescription(),
			'expire' => $this->getExpire(),
			'forceConfidentialComments' => $this->getForceConfidentialComments(),
			'hideBookedUp' => boolval($this->getHideBookedUp()),
			'maxVotesPerOption' => $this->getOptionLimit(),
			'maxVotesPerUser' => $this->getVoteLimit(),
			'proposalsExpire' => $this->getProposalsExpire(),
			'showResults' => $this->getShowResults(),
			'title' => $this->getTitle(),
			'useNo' => boolval($this->getUseNo()),
		];
	}

	public function getCurrentUserStatus(): array {
		return [
			'groupInvitations' => $this->getGroupShares(),
			'isInvolved' => $this->getIsInvolved(),
			'isLocked' => boolval($this->getIsCurrentUserLocked()),
			'isLoggedIn' => $this->userSession->getIsLoggedIn(),
			'isNoUser' => !$this->userSession->getIsLoggedIn(),
			'isOwner' => $this->getIsPollOwner(),
			'orphanedVotes' => $this->getCurrentUserOrphanedVotes(),
			'shareToken' => $this->getShareToken(),
			'userId' => $this->userSession->getCurrentUserId(),
			'userRole' => $this->getUserRole(),
			'countVotes' => $this->getCurrentUserVotes(),
			'yesVotes' => $this->getCurrentUserVotesYes(),
			'noVotes' => $this->getCurrentUserVotesNo(),
			'maybeVotes' => $this->getCurrentUserVotesMaybe(),
			'pollGroupUserShares' => $this->getPollGroupUserShares(),
		];
	}
	public function getPermissionsArray(): array {
		return [
			'addOptions' => $this->getIsAllowed(self::PERMISSION_OPTION_ADD),
			'addShares' => $this->getIsAllowed(self::PERMISSION_SHARE_ADD),
			'addSharesExternal' => $this->getIsAllowed(self::PERMISSION_SHARE_ADD_EXTERNAL),
			'archive' => $this->getIsAllowed(self::PERMISSION_POLL_ARCHIVE),
			'changeForeignVotes' => $this->getIsAllowed(self::PERMISSION_VOTE_FOREIGN_CHANGE),
			'changeOwner' => $this->getIsAllowed(self::PERMISSION_POLL_CHANGE_OWNER),
			'clone' => $this->getIsAllowed(self::PERMISSION_OPTION_CLONE),
			'comment' => $this->getIsAllowed(self::PERMISSION_COMMENT_ADD),
			'confirmOptions' => $this->getIsAllowed(self::PERMISSION_OPTION_CONFIRM),
			'deanonymize' => $this->getIsAllowed(self::PERMISSION_DEANONYMIZE),
			'delete' => $this->getIsAllowed(self::PERMISSION_POLL_DELETE),
			'edit' => $this->getIsAllowed(self::PERMISSION_POLL_EDIT),
			'reorderOptions' => $this->getIsAllowed(self::PERMISSION_OPTIONS_REORDER),
			'seeResults' => $this->getIsAllowed(self::PERMISSION_POLL_RESULTS_VIEW),
			'seeUsernames' => $this->getIsAllowed(self::PERMISSION_POLL_USERNAMES_VIEW),
			'shiftOptions' => $this->getIsAllowed(self::PERMISSION_OPTIONS_SHIFT),
			'subscribe' => $this->getIsAllowed(self::PERMISSION_POLL_SUBSCRIBE),
			'takeOver' => $this->getIsAllowed(self::PERMISSION_POLL_TAKEOVER),
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
		$this->setAnonymousSafe($pollConfiguration['anonymous'] ?? $this->getAnonymous());
		$this->setAutoReminder($pollConfiguration['autoReminder'] ?? $this->getAutoReminder());
		$this->setCollapseDescription($pollConfiguration['collapseDescription'] ?? $this->getCollapseDescription());
		$this->setExpire($pollConfiguration['expire'] ?? $this->getExpire());
		$this->setForceConfidentialComments($pollConfiguration['forceConfidentialComments'] ?? $this->getForceConfidentialComments());
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
		if ($this->getCurrentUserIsEntityUser()) {
			return self::ROLE_OWNER;
		}

		$evaluatedRole = $this->userRole;

		// If user is not a poll admin (set by normal poll share) and poll group shares exist,
		// iterate over the share types and return the higher role
		if ($this->getPollGroupUserShares() && !$evaluatedRole) {
			// return the higher role of the group shares
			foreach ($this->getPollGroupUserShares() as $shareType) {
				if ($shareType === self::ROLE_ADMIN) {
					$evaluatedRole = self::ROLE_ADMIN;
				}
				return self::ROLE_USER;
			}
		}

		if ($this->getIsCurrentUserLocked() && $this->userRole === self::ROLE_ADMIN) {
			return self::ROLE_USER;
		}

		if ($this->userSession->getShareType() === Share::TYPE_PUBLIC) {
			return Share::TYPE_PUBLIC;
		}

		return $evaluatedRole;
	}

	public function getVoteUrl(): string {
		return $this->urlGenerator->linkToRouteAbsolute(
			AppConstants::APP_ID . '.page.vote',
			['id' => $this->getId()]
		);
	}

	/**
	 * Set anonymous setting
	 * If setting has negative value, it is locked and cannot be changed
	 * @param bool|int $value - true for anonymous, false for non-anonymous
	 */
	public function setAnonymousSafe(bool|int $value): void {
		// if anonymous is locked, do not allow changes
		if ($this->getAnonymous() < 0) {
			return;
		}

		if (!$this->getAllowDeanonymize() && $value > 0) {
			// if the owner of the poll is restricted, lock the anonymous
			// setting once it is set
			// lock anonymous setting by setting it to negative value
			$value = -1;
		}
		$this->setAnonymous((int)$value);
	}

	private function setAutoReminder(bool|int $value): void {
		$this->setMiscSettingsByKey('autoReminder', (bool)$value);
	}

	private function getAutoReminder(): bool {
		return $this->getMiscSettingsArray()['autoReminder'] ?? false;
	}

	private function setForceConfidentialComments(bool|int $value): void {
		$this->setMiscSettingsByKey('forceConfidentialComments', (bool)$value);
	}

	public function getForceConfidentialComments(): bool {
		return $this->getMiscSettingsArray()['forceConfidentialComments'] ?? false;
	}

	private function setCollapseDescription(bool|int $value): void {
		$this->setMiscSettingsByKey('collapseDescription', (bool)$value);
	}

	private function getCollapseDescription(): bool {
		return $this->getMiscSettingsArray()['collapseDescription'] ?? true;
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

	private function getGroupShares(): array {
		if ($this->groupShares !== null && $this->groupShares !== '') {
			// explode with separator and remove empty elements
			return array_filter(explode(PollMapper::CONCAT_SEPARATOR, PollMapper::CONCAT_SEPARATOR . $this->groupShares));
		}

		return [];
	}

	/**
	 * Return the poll groups this poll belongs to
	 * @return int[]
	 *
	 * @psalm-return list<int>
	 */
	public function getPollGroups(): array {
		if (!$this->pollGroups) {
			return [];
		}
		return array_map('intval', explode(PollGroup::CONCAT_SEPARATOR, $this->pollGroups));
	}

	/**
	 * Returns the sharetypes of the poll group this poll belongs to
	 *
	 * @return string[]
	 *
	 * @psalm-return list<string>
	 */
	public function getPollGroupUserShares(): array {
		if (!$this->pollGroupUserShares) {
			return [];
		}
		return explode(PollGroup::CONCAT_SEPARATOR, $this->pollGroupUserShares);
	}

	private function getAccess(): string {
		if ($this->access === self::ACCESS_PUBLIC) {
			return self::ACCESS_OPEN;
		}
		if ($this->access === self::ACCESS_HIDDEN) {
			return self::ACCESS_PRIVATE;
		}
		return $this->access;
	}

	private function getProposalsExpired(): bool {
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

	private function getDescriptionSafe(): string {
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

	private function getRelevantThreshold(): int {
		return max(
			$this->getCreated(),
			$this->getLastInteraction(),
			$this->getExpire(),
			$this->getMaxDate(),
		);
	}

	private function getDeletionDate(): int {
		if ($this->getDeleted() > 0 && $this->appSettings->getAutoDeleteEnabled()) {
			return $this->getDeleted() + ($this->appSettings->getAutoDeleteOffsetDays() * 60 * 60 * 24);
		}
		return 0;
	}

	private function getIsCurrentUserLocked(): bool {
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
	public function request(string $permission): bool {
		if (!$this->getIsAllowed($permission)) {
			throw new ForbiddenException('denied permission ' . $permission);
		}
		return true;
	}

	/**
	 * Check particular rights and inform via boolean value, if the right is granted or denied
	 */
	public function getIsAllowed(string $permission): bool {
		return match ($permission) {
			self::PERMISSION_COMMENT_ADD => $this->getAllowCommenting(),
			self::PERMISSION_COMMENT_DELETE => $this->getAllowDeleteComment(),
			self::PERMISSION_OPTION_ADD => $this->getAllowAddOptions(),
			self::PERMISSION_OPTION_CONFIRM => $this->getAllowConfirmOption(),
			self::PERMISSION_OPTION_CLONE => $this->getAllowCloneOption(),
			self::PERMISSION_OPTION_DELETE => $this->getAllowDeleteOption(),
			self::PERMISSION_OPTIONS_SHIFT => $this->getAllowShiftOptions(),
			self::PERMISSION_OPTIONS_REORDER => $this->getAllowReorderOptions(),
			self::PERMISSION_OVERRIDE => true,
			self::PERMISSION_POLL_VIEW => $this->getAllowAccessPoll(),
			self::PERMISSION_POLL_EDIT => $this->getAllowEditPoll(),
			self::PERMISSION_POLL_DELETE => $this->getAllowDeletePoll(),
			self::PERMISSION_POLL_ARCHIVE => $this->getAllowEditPoll(),
			self::PERMISSION_POLL_TAKEOVER => $this->getAllowTakeOver(),
			self::PERMISSION_POLL_CHANGE_OWNER => $this->getAllowChangeOwner(),
			self::PERMISSION_POLL_SUBSCRIBE => $this->getAllowSubscribeToPoll(),
			self::PERMISSION_POLL_RESULTS_VIEW => $this->getAllowShowResults(),
			self::PERMISSION_POLL_USERNAMES_VIEW => $this->getAllowEditPoll() || !$this->getAnonymous(),
			self::PERMISSION_VOTE_EDIT => $this->getAllowVote(),
			self::PERMISSION_VOTE_FOREIGN_CHANGE => $this->getAllowChangeForeignVotes(),
			self::PERMISSION_SHARE_ADD => $this->systemSettings->getShareCreateAllowed(),
			self::PERMISSION_SHARE_ADD_EXTERNAL => $this->systemSettings->getExternalShareCreationAllowed(),
			self::PERMISSION_DEANONYMIZE => $this->getAllowDeanonymize(),
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
			return ($this->userSession->getCurrentUser()->getIsInGroup($groupName));
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

	private function getAllowTakeOver(): bool {
		return $this->userSession->getCurrentUser()->getIsAdmin();
	}

	/**
	 * Checks, if user is allowed to edit the poll configuration
	 **/
	private function getAllowChangeOwner(): bool {
		return $this->getAllowEditPoll()
		|| $this->userSession->getCurrentUser()->getIsAdmin();
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
		return $this->userSession->getCurrentUser()->getIsAdmin();
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

	private function getAllowShiftOptions(): bool {
		return $this->getAllowEditPoll() && $this->getProposalsCount() === 0;
	}


	private function getAllowCloneOption(): bool {
		return $this->getAllowEditPoll();
	}

	/**
	 * Is current user allowed to delete options from poll
	 */
	private function getAllowDeleteOption(): bool {
		return $this->getIsPollOwner() || $this->getIsDelegatedAdmin();
	}

	/**
	 * Is current user allowed to confirm options
	 */
	private function getAllowConfirmOption(): bool {
		return $this->getAllowEditPoll() && $this->getExpired();
	}

	/**
	 * Is current user allowed to confirm options
	 */
	private function getAllowReorderOptions(): bool {
		return $this->getAllowEditPoll() && !$this->getExpired() && $this->getType() === Poll::TYPE_TEXT;
	}

	/**
	 * Compare $userId with current user's id
	 */
	public function matchUser(string $userId): bool {
		return (bool)$this->userSession->getCurrentUser()->getId() && $this->userSession->getCurrentUser()->getId() === $userId;
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
	 * Checks, if poll owner is allowed to change votes
	 **/
	private function getAllowChangeForeignVotes(): bool {
		return $this->getAnonymous() > -1 && $this->getAllowEditPoll() && $this->getUser()->getIsUnrestrictedPollOwner();
	}

	/**
	 * Checks, if poll owner is allowed to deanonymize votes
	 **/
	private function getAllowDeanonymize(): bool {
		// Current user is allowed to edit the poll and the owner of the poll is unrestricted
		return $this->getAnonymous() > -1 && $this->getAllowEditPoll() && $this->getUser()->getIsUnrestrictedPollOwner();
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

		return $this->userSession->getCurrentUser()->getHasEmail();
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
