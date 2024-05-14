<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 * @author Kai Schröer <git@schroeer.co>
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

namespace OCA\Polls\Db;

use JsonSerializable;
use OCA\Polls\AppConstants;
use OCA\Polls\Exceptions\NoDeadLineException;
use OCA\Polls\Helper\Container;
use OCA\Polls\UserSession;
use OCP\IURLGenerator;

/**
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
 *
 * Magic functions for subqueried columns
 * @method int getCurrentUserCountOrphanedVotes()
 * @method int getCurrentUserCountVotes()
 * @method int getCurrentUserCountVotesYes()
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
	protected string $userRole = "none";
	
	// subqueried columns
	/** @psalm-suppress PossiblyUnusedProperty */
	protected int $currentUserCountOrphanedVotes = 0;
	/** @psalm-suppress PossiblyUnusedProperty */
	protected int $currentUserCountVotes = 0;
	/** @psalm-suppress PossiblyUnusedProperty */
	protected int $currentUserCountVotesYes = 0;

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

		// subqueried columns
		$this->addType('currentUserCountVotes', 'int');
		$this->addType('currentUserCountVotesYes', 'int');
		$this->addType('currentUserCountOrphanedVotes', 'int');

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
			'title' => $this->getTitle(),
			'description' => $this->getDescription(),
			'descriptionSafe' => $this->getDescriptionSafe(),
			'owner' => $this->getUser(),
			'access' => $this->getAccess(),
			'allowComment' => $this->getAllowComment(),
			'allowMaybe' => $this->getAllowMaybe(),
			'allowProposals' => $this->getAllowProposals(),
			'anonymous' => $this->getAnonymous(),
			'autoReminder' => $this->getAutoReminder(),
			'created' => $this->getCreated(),
			'deleted' => $this->getDeleted(),
			'expire' => $this->getExpire(),
			'hideBookedUp' => $this->getHideBookedUp(),
			'optionLimit' => $this->getOptionLimit(),
			'proposalsExpire' => $this->getProposalsExpire(),
			'showResults' => $this->getShowResults() === 'expired' ? Poll::SHOW_RESULTS_CLOSED : $this->getShowResults(),
			'useNo' => $this->getUseNo(),
			'voteLimit' => $this->getVoteLimit(),
			'lastInteraction' => $this->getLastInteraction(),
			'summary' => [
				'userRole' => $this->getUserRole(),
				'orphanedVotes' => $this->getCurrentUserCountOrphanedVotes(),
				'yesByCurrentUser' => $this->getCurrentUserCountVotesYes(),
				'countVotes' => $this->getCurrentUserCountVotes(),
			],
		];
	}

	/**
	 * @return static
	 */
	public function deserializeArray(array $array): self {
		$this->setAccess($array['access'] ?? $this->getAccess());
		$this->setAllowComment($array['allowComment'] ?? $this->getAllowComment());
		$this->setAllowMaybe($array['allowMaybe'] ?? $this->getAllowMaybe());
		$this->setAllowProposals($array['allowProposals'] ?? $this->getAllowProposals());
		$this->setAdminAccess($array['adminAccess'] ?? $this->getAdminAccess());
		$this->setAnonymous($array['anonymous'] ?? $this->getAnonymous());
		$this->setAutoReminder($array['autoReminder'] ?? $this->getAutoReminder());
		$this->setDescription($array['description'] ?? $this->getDescription());
		$this->setDeleted($array['deleted'] ?? $this->getDeleted());
		$this->setExpire($array['expire'] ?? $this->getExpire());
		$this->setHideBookedUp($array['hideBookedUp'] ?? $this->getHideBookedUp());
		$this->setOptionLimit($array['optionLimit'] ?? $this->getOptionLimit());
		$this->setProposalsExpire($array['proposalsExpire'] ?? $this->getProposalsExpire());
		$this->setShowResults($array['showResults'] ?? $this->getShowResults());
		$this->setTitle($array['title'] ?? $this->getTitle());
		$this->setUseNo($array['useNo'] ?? $this->getUseNo());
		$this->setVoteLimit($array['voteLimit'] ?? $this->getVoteLimit());
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
			return 'owner';
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
		$this->setMiscSettingsByKey('autoReminder', (bool) $value);
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

	public function getRelevantThresholdNet(): int {
		return max(
			$this->getCreated(),
			$this->getLastInteraction(),
			$this->getExpire(),
			$this->getMaxDate(),
		);
	}

	/** @psalm-suppress PossiblyUnusedMethod */
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
}
