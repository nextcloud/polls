<?php
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
use OCA\Polls\AppInfo\AppConstants;
use OCA\Polls\Exceptions\NoDeadLineException;
use OCA\Polls\Helper\Container;
use OCP\IURLGenerator;

/**
 * @method int getId()
 * @method void setId(integer $value)
 * @method string getType()
 * @method void setType(string $value)
 * @method string getTitle()
 * @method void setTitle(string $value)
 * @method string getDescription()
 * @method void setDescription(string $value)
 * @method string getOwner()
 * @method void setOwner(string $value)
 * @method int getCreated()
 * @method void setCreated(integer $value)
 * @method int getExpire()
 * @method void setExpire(integer $value)
 * @method int getDeleted()
 * @method void setDeleted(integer $value)
 * @method string getAccess()
 * @method void setAccess(string $value)
 * @method int getAnonymous()
 * @method void setAnonymous(integer $value)
 * @method int getAllowComment()
 * @method void setAllowComment(integer $value)
 * @method int getAllowMaybe()
 * @method void setAllowMaybe(integer $value)
 * @method string getAllowProposals()
 * @method void setAllowProposals(string $value)
 * @method int getProposalsExpire()
 * @method void setProposalsExpire(integer $value)
 * @method int getVoteLimit()
 * @method void setVoteLimit(integer $value)
 * @method int getOptionLimit()
 * @method void setOptionLimit(integer $value)
 * @method string getShowResults()
 * @method void setShowResults(string $value)
 * @method int getAdminAccess()
 * @method void setAdminAccess(integer $value)
 * @method int getImportant()
 * @method void setImportant(integer $value)
 * @method int getHideBookedUp()
 * @method void setHideBookedUp(integer $value)
 * @method int getUseNo()
 * @method void setUseNo(integer $value)
 * @method int getLastInteraction()
 * @method void setLastInteraction(integer $value)
 * @method string getMiscSettings()
 * @method void setMiscSettings(string $value)
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
	private OptionMapper $optionMapper;

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
	protected int $important = 0;
	protected int $allowComment = 0;
	protected int $hideBookedUp = 0;
	protected int $useNo = 0;
	protected int $lastInteraction = 0;
	protected ?string $miscSettings = '';

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
		$this->addType('important', 'int');
		$this->addType('hideBookedUp', 'int');
		$this->addType('useNo', 'int');
		$this->addType('lastInteraction', 'int');
		$this->optionMapper = Container::queryClass(OptionMapper::class);
		$this->urlGenerator = Container::queryClass(IURLGenerator::class);
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->getId(),
			'access' => $this->getAccess(),
			'adminAccess' => $this->getAdminAccess(),
			'allowComment' => $this->getAllowComment(),
			'allowMaybe' => $this->getAllowMaybe(),
			'allowProposals' => $this->getAllowProposals(),
			'anonymous' => $this->getAnonymous(),
			'autoReminder' => $this->getAutoReminder(),
			'created' => $this->getCreated(),
			'deleted' => $this->getDeleted(),
			'description' => $this->getDescription(),
			'descriptionSafe' => $this->getDescriptionSafe(),
			'expire' => $this->getExpire(),
			'hideBookedUp' => $this->getHideBookedUp(),
			'important' => $this->getImportant(),
			'optionLimit' => $this->getOptionLimit(),
			'owner' => $this->getUser(),
			'proposalsExpire' => $this->getProposalsExpire(),
			'showResults' => $this->getShowResults() === 'expired' ? Poll::SHOW_RESULTS_CLOSED : $this->getShowResults(),
			'title' => $this->getTitle(),
			'type' => $this->getType(),
			'useNo' => $this->getUseNo(),
			'voteLimit' => $this->getVoteLimit(),
			'lastInteraction' => $this->getLastInteraction(),
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
		$this->setImportant($array['important'] ?? $this->getImportant());
		$this->setOptionLimit($array['optionLimit'] ?? $this->getOptionLimit());
		$this->setProposalsExpire($array['proposalsExpire'] ?? $this->getProposalsExpire());
		$this->setShowResults($array['showResults'] ?? $this->getShowResults());
		$this->setTitle($array['title'] ?? $this->getTitle());
		$this->setUseNo($array['useNo'] ?? $this->getUseNo());
		$this->setVoteLimit($array['voteLimit'] ?? $this->getVoteLimit());
		return $this;
	}

	public function getExpired(): bool {
		return (
			$this->getExpire() > 0
			&& $this->getExpire() < time()
		);
	}

	public function getUri(): string {
		return self::URI_PREFIX . $this->getId();
	}

	public function getVoteUrl() : string {
		return $this->urlGenerator->linkToRouteAbsolute(
			AppConstants::APP_ID . '.page.vote',
			['id' => $this->getId()]
		);
	}

	public function setAutoReminder(bool $value) : void {
		$this->setMiscSettingsByKey('autoReminder', $value);
	}

	public function getAutoReminder(): bool {
		return $this->getMiscSettingsArray()['autoReminder'] ?? false;
	}

	// alias of getId()
	public function getPollId(): int {
		return $this->getId();
	}

	// alias of getOwner()
	public function getUserId() : string {
		return $this->getOwner();
	}

	// alias of setOwner($value)
	public function setUserId(string $userId) : void {
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


	private function setMiscSettingsArray(array $value) : void {
		$this->setMiscSettings(json_encode($value));
	}

	private function getMiscSettingsArray() : array {
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
		
		if ($deadline - $this->getCreated() > self::FIVE_DAYS
			&& $deadline - $time < self::TWO_DAYS
			&& $deadline > $time
		) {
			return self::TWO_DAYS;
		}

		if ($deadline - $this->getCreated() > self::TWO_DAYS
			&& $deadline - $time < self::ONE_AND_HALF_DAY
			&& $deadline > $time
		) {
			return self::ONE_AND_HALF_DAY;
		}
		throw new NoDeadLineException();
	}

	public function getDeadline(): int {
		if ($this->getExpire()) {
			return $this->getExpire();
		}

		if ($this->getType() === Poll::TYPE_DATE) {
			// use first date option as reminder deadline
			return $this->optionMapper->findDateBoundaries($this->getId())['min'];
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
