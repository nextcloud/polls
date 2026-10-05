<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Event\PollArchivedEvent;
use OCA\Polls\Event\PollCloseEvent;
use OCA\Polls\Event\PollCreatedEvent;
use OCA\Polls\Event\PollDeletedEvent;
use OCA\Polls\Event\PollOwnerChangeEvent;
use OCA\Polls\Event\PollReopenEvent;
use OCA\Polls\Event\PollRestoredEvent;
use OCA\Polls\Event\PollUpdatedEvent;
use OCA\Polls\Exceptions\AlreadyDeletedException;
use OCA\Polls\Exceptions\EmptyTitleException;
use OCA\Polls\Exceptions\ForbiddenException;
use OCA\Polls\Exceptions\InvalidAccessException;
use OCA\Polls\Exceptions\InvalidPollListParameterException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Exceptions\InvalidShowResultsException;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\NotFoundException;
use OCA\Polls\Exceptions\UserNotFoundException;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Model\UserBase;
use OCA\Polls\UserSession;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserManager;
use OCP\Search\ISearchQuery;

class PollService {
	public const SORT_CREATED = 'created';
	public const SORT_TITLE = 'title';
	public const SORT_ACCESS = 'access';
	public const SORT_OWNER = 'owner';
	public const SORT_EXPIRE = 'expire';
	public const SORT_INTERACTION = 'interaction';
	public const SORT_COLUMNS = [
		self::SORT_CREATED,
		self::SORT_TITLE,
		self::SORT_ACCESS,
		self::SORT_OWNER,
		self::SORT_EXPIRE,
		self::SORT_INTERACTION,
	];
	public const SORT_ASCENDING = 'asc';
	public const SORT_DESCENDING = 'desc';
	public const SORT_DIRECTIONS = [
		self::SORT_ASCENDING,
		self::SORT_DESCENDING,
	];
	public const MAX_PAGE_SIZE = 100;
	/**
	 * Sort columns, which the database can sort equally to sortPolls()
	 * Title, access and owner are sorted by their evaluated value in PHP
	 */
	private const SORT_SQL_COLUMNS = [
		self::SORT_CREATED => 'created',
		self::SORT_EXPIRE => 'expire',
		self::SORT_INTERACTION => 'last_interaction',
	];

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		private AppSettings $appSettings,
		private IEventDispatcher $eventDispatcher,
		private IUserManager $userManager,
		private Poll $poll,
		private PollMapper $pollMapper,
		private UserMapper $userMapper,
		private UserSession $userSession,
		private VoteMapper $voteMapper,
	) {
	}

	/**
	 * Get the polls, the current user has access to
	 * The optional filters narrow down the polls already in the database query
	 *
	 * @param string|null $category see PollMapper::findForMe()
	 * @param int|null $pollGroupId see PollMapper::findForMe()
	 * @param string|null $type see PollMapper::findForMe()
	 * @return Poll[]
	 */
	public function listPolls(?string $category = null, ?int $pollGroupId = null, ?string $type = null): array {
		$pollList = $this->pollMapper->findForMe($this->userSession->getCurrentUserId(), $category, $pollGroupId, $type);
		if ($this->userSession->getCurrentUser()->getIsAdmin()) {
			return $pollList;
		}

		return array_values(array_filter($pollList, function (Poll $poll): bool {
			return $poll->getIsAllowed(Poll::PERMISSION_POLL_ACCESS);
		}));
	}

	/**
	 * Get one page of the current user's polls, filtered by category or poll group
	 *
	 * @param string $category one of Poll::CATEGORIES, ignored if $pollGroupId is set
	 * @param int|null $pollGroupId limit the list to the polls of this poll group
	 * @param string|null $type limit the list to Poll::TYPE_DATE or Poll::TYPE_TEXT
	 * @param string $sortBy one of self::SORT_COLUMNS
	 * @param string $sortDirection one of self::SORT_DIRECTIONS
	 * @return array{polls: Poll[], total: int}
	 * @throws InvalidPollListParameterException
	 */
	public function listPollsPaged(
		string $category,
		?int $pollGroupId,
		?string $type,
		string $sortBy,
		string $sortDirection,
		int $offset,
		int $limit,
	): array {
		if ($pollGroupId === null && !in_array($category, Poll::CATEGORIES, true)) {
			throw new InvalidPollListParameterException('Invalid category ' . $category);
		}
		if ($type !== null && !in_array($type, [Poll::TYPE_DATE, Poll::TYPE_TEXT], true)) {
			throw new InvalidPollListParameterException('Invalid poll type ' . $type);
		}
		if (!in_array($sortBy, self::SORT_COLUMNS, true)) {
			throw new InvalidPollListParameterException('Invalid sort column ' . $sortBy);
		}
		if (!in_array($sortDirection, self::SORT_DIRECTIONS, true)) {
			throw new InvalidPollListParameterException('Invalid sort direction ' . $sortDirection);
		}
		if ($offset < 0 || $limit < 1 || $limit > self::MAX_PAGE_SIZE) {
			throw new InvalidPollListParameterException('Invalid offset or limit');
		}

		$descending = $sortDirection === self::SORT_DESCENDING;

		// the database can do the paging, if it filters the category exactly and
		// sorts like sortPolls() would
		if ($pollGroupId === null
			&& $this->hasExactCategoryCondition($category)
			&& isset(self::SORT_SQL_COLUMNS[$sortBy])
		) {
			$userId = $this->userSession->getCurrentUserId();
			return [
				'polls' => $this->pollMapper->findPageForMe(
					$userId,
					$category,
					$type,
					self::SORT_SQL_COLUMNS[$sortBy],
					$descending,
					$offset,
					$limit,
				),
				'total' => $this->pollMapper->countForMe($userId, $category, null, $type),
			];
		}

		// type and poll group are filtered exactly by the query,
		// the category query is only a superset of the permission based categories
		$polls = $this->listPolls($category, $pollGroupId, $type);
		if ($pollGroupId === null) {
			$polls = array_filter(
				$polls,
				fn (Poll $poll): bool => in_array($category, $poll->getCategories(), true),
			);
		}
		$polls = $this->sortPolls($polls, $sortBy, $descending);

		return [
			'polls' => array_slice($polls, $offset, $limit),
			'total' => count($polls),
		];
	}

	/**
	 * Whether PollMapper::getCategoryCondition() matches Poll::getCategories()
	 * exactly for this category, so the entity check can be skipped
	 *
	 * All other categories depend on permissions, which are only evaluated by
	 * the entity, like group memberships or locked shares.
	 */
	private function hasExactCategoryCondition(string $category): bool {
		return match ($category) {
			// the owner always has access to their own polls, archived polls are
			// limited to the current user's polls by the query anyway
			Poll::CATEGORY_MY, Poll::CATEGORY_ARCHIVED => true,
			// open polls are only accessible for logged in users
			Poll::CATEGORY_OPEN => $this->userSession->getIsLoggedIn(),
			default => false,
		};
	}

	/**
	 * Count the polls per category and per poll group without serializing any poll
	 *
	 * The polls still have to be loaded, because the categories depend on
	 * permissions, which are evaluated by the entity.
	 *
	 * @return array{counts: array<string, int>, pollGroupCounts: array<int, int>}
	 */
	public function getPollListCounts(): array {
		$counts = array_fill_keys(Poll::CATEGORIES, 0);
		$pollGroupCounts = [];

		foreach ($this->listPolls() as $poll) {
			foreach ($poll->getCategories() as $category) {
				$counts[$category]++;
			}
			foreach ($poll->getPollGroups() as $pollGroupId) {
				$pollGroupCounts[$pollGroupId] = ($pollGroupCounts[$pollGroupId] ?? 0) + 1;
			}
		}

		return [
			'counts' => $counts,
			'pollGroupCounts' => $pollGroupCounts,
		];
	}

	/**
	 * Sort the polls deterministically, ties are broken by the poll id
	 *
	 * @param Poll[] $polls
	 * @return Poll[]
	 */
	private function sortPolls(array $polls, string $sortBy, bool $descending): array {
		$displayNames = [];
		$sortValue = match ($sortBy) {
			self::SORT_TITLE => fn (Poll $poll): string => $poll->getTitle(),
			self::SORT_ACCESS => fn (Poll $poll): string => $poll->getAccess(),
			self::SORT_OWNER => function (Poll $poll) use (&$displayNames): string {
				$owner = (string)$poll->getOwner();
				return $displayNames[$owner] ??= $this->userManager->getDisplayName($owner) ?? $owner;
			},
			self::SORT_EXPIRE => fn (Poll $poll): int => $poll->getExpire(),
			self::SORT_INTERACTION => fn (Poll $poll): int => $poll->getLastInteraction(),
			default => fn (Poll $poll): int => $poll->getCreated(),
		};

		$keyed = array_map(fn (Poll $poll): array => [$sortValue($poll), $poll], array_values($polls));
		$direction = $descending ? -1 : 1;
		usort($keyed, function (array $a, array $b) use ($direction): int {
			$compare = is_string($a[0])
				? strnatcasecmp($a[0], $b[0])
				: $a[0] <=> $b[0];

			// the database order of ties is undefined, so break them by id like
			// PollMapper::findPageForMe() does, otherwise paging over equal sort
			// values could skip or repeat polls
			return $direction * ($compare !== 0 ? $compare : $a[1]->getId() <=> $b[1]->getId());
		});

		return array_column($keyed, 1);
	}

	/**
	 * Get list of polls
	 */
	public function search(ISearchQuery $query): array {
		$pollList = [];
		try {
			$polls = $this->pollMapper->search($query);

			foreach ($polls as $poll) {
				try {
					$poll->request(Poll::PERMISSION_POLL_ACCESS);
					$pollList[] = $poll;
				} catch (ForbiddenException $e) {
					continue;
				}
			}
		} catch (DoesNotExistException $e) {
			// silent catch
		}
		return $pollList;
	}

	/**
	 * Get list of polls
	 * @return Poll[]
	 */
	public function listForAdmin(): array {
		$pollList = [];
		if ($this->userSession->getCurrentUser()->getIsAdmin()) {
			try {
				$pollList = $this->pollMapper->findForAdmin($this->userSession->getCurrentUserId());
			} catch (DoesNotExistException $e) {
				// silent catch
			}
		}
		return $pollList;
	}

	/**
	 * @return Poll[]
	 * @psalm-return array<Poll>
	 */
	public function transferPolls(string $sourceUserId, string $targetUserId): array {
		try {
			$targetUser = $this->userMapper->getUserFromUserBase($targetUserId);
		} catch (UserNotFoundException $e) {
			throw new InvalidUsernameException('The user id "' . $targetUserId . '" for the target user is not valid.');
		}

		$pollsToTransfer = $this->pollMapper->listByOwner($sourceUserId);

		foreach ($pollsToTransfer as &$poll) {
			$poll = $this->transferPoll($poll, $targetUser);
		}
		return $pollsToTransfer;
	}

	/**
	 * Update poll configuration
	 * @return Poll
	 */
	public function takeover(int $pollId, ?UserBase $targetUser = null): Poll {
		if ($targetUser === null) {
			$targetUser = $this->userSession->getCurrentUser();
		}
		return $this->transferPoll($pollId, $targetUser);
	}

	/**
	 * Transfer ownership of a poll
	 * @param int|Poll $poll poll or pollId of poll to transfer ownership
	 * @param string|UserBase $targetUser User to transfer polls to. If null the current user will be used
	 */
	public function transferPoll(int|Poll $poll, string|UserBase $targetUser): Poll {
		if (!($poll instanceof Poll)) {
			$poll = $this->pollMapper->get($poll);
		}

		$poll->request(Poll::PERMISSION_POLL_CHANGE_OWNER);

		if (!($targetUser instanceof UserBase)) {
			$userId = $targetUser;
			try {
				$targetUser = $this->userMapper->getUserFromUserBase($userId);
			} catch (UserNotFoundException $e) {
				// to keep psalm quiet
				throw new InvalidUsernameException('The user id "' . $userId . '" for the target user is not valid.');
			}
		}

		$oldOwner = $poll->getOwner();

		$poll->setOwner($targetUser->getId());
		$poll = $this->pollMapper->update($poll);

		$this->eventDispatcher->dispatchTyped(new PollOwnerChangeEvent($poll, $oldOwner, $poll->getOwner()));

		return $poll;
	}

	/**
	 * get poll configuration
	 * @return Poll
	 */
	public function get(int $pollId) {
		try {
			$this->poll = $this->pollMapper->get($pollId);
			$this->poll->request(Poll::PERMISSION_POLL_ACCESS);
			return $this->poll;
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Poll not found');
		}
	}

	public function getPollOwnerFromDB(int $pollId): UserBase {
		try {
			$poll = $this->pollMapper->get($pollId);
			return $poll->getUser();
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Poll not found');
		}
	}
	/**
	 * Add poll
	 */
	public function add(string $type, string $title, string $votingVariant = Poll::VARIANT_SIMPLE): Poll {
		if (!$this->appSettings->getPollCreationAllowed()) {
			throw new ForbiddenException('Poll creation is disabled');
		}

		// Validate valuess
		if (!in_array($type, $this->getValidPollType())) {
			throw new InvalidPollTypeException('Invalid poll type');
		}

		if (!$title) {
			throw new EmptyTitleException('Title must not be empty');
		}

		$timestamp = time();
		$this->poll = new Poll();
		$this->poll->setType($type);
		$this->poll->setVotingVariant($votingVariant);
		$this->poll->setTitle($title);
		$this->poll->setCreated($timestamp);
		$this->poll->setLastInteraction($timestamp);
		$this->poll->setOwner($this->userSession->getCurrentUserId());

		// create new poll before resetting all values to
		// ensure that the poll has all required values and an id
		// later checks may fail if the poll has no id
		$this->poll = $this->pollMapper->insert($this->poll);

		$this->poll->setDescription('');
		$this->poll->setAccess(Poll::ACCESS_PRIVATE);
		$this->poll->setExpire(0);
		$this->poll->setAnonymousSafe(0);
		$this->poll->setAllowMaybe(0);
		$this->poll->setVoteLimit(0);
		$this->poll->setShowResults(Poll::SHOW_RESULTS_ALWAYS);
		$this->poll->setDeleted(0);
		$this->poll->setAdminAccess(0);

		$this->pollMapper->update($this->poll);

		$this->eventDispatcher->dispatchTyped(new PollCreatedEvent($this->poll));

		return $this->poll;
	}

	/**
	 * Update poll configuration
	 *
	 * @param int $pollId Poll id
	 * @param array $pollConfiguration Poll configuration
	 * @return array
	 *
	 * @psalm-return array{poll: Poll, diff: array, changes: array}
	 */
	public function update(int $pollId, array $pollConfiguration): array {
		$this->poll = $this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_EDIT);

		// Validate valuess
		if (isset($pollConfiguration['showResults']) && !in_array($pollConfiguration['showResults'], $this->getValidShowResults())) {
			throw new InvalidShowResultsException('Invalid value for prop showResults');
		}

		if (isset($pollConfiguration['title']) && !$pollConfiguration['title']) {
			throw new EmptyTitleException('Title must not be empty');
		}

		if (isset($pollConfiguration['anonymous'])
			&& $pollConfiguration['anonymous'] === 0
			&& $this->poll->getAnonymous() < 0
		) {
			throw new ForbiddenException('Deanonimization is not allowed');
		}

		if (isset($pollConfiguration['access'])) {
			if (!in_array($pollConfiguration['access'], $this->getValidAccess())) {
				throw new InvalidAccessException('Invalid value for prop access ' . $pollConfiguration['access']);
			}

			if ($pollConfiguration['access'] === (Poll::ACCESS_OPEN)) {
				$this->appSettings->getAllAccessAllowed();
			}
		}

		// Set the expiry time to the actual servertime to avoid an
		// expiry misinterpration when using permission checks
		if (isset($pollConfiguration['expire']) && $pollConfiguration['expire'] < 0) {
			$pollConfiguration['expire'] = time();
		}

		$diff = new DiffService($this->poll);

		$this->poll->deserializeArray($pollConfiguration);
		$this->poll = $this->pollMapper->update($this->poll);
		$this->eventDispatcher->dispatchTyped(new PollUpdatedEvent($this->poll));

		$diff->setComparisonObject($this->poll);

		return [
			'poll' => $this->poll,
			'diff' => $diff->getFullDiff(),
			'changes' => $diff->getNewValuesDiff(),
		];
	}

	/**
	 * Manually lock anonymization
	 * @return Poll
	 */
	public function lockAnonymous(int $pollId): Poll {
		$this->poll = $this->pollMapper->get($pollId);

		// Only possible, if poll is already anonymized
		if ($this->poll->getAnonymous() < 1) {
			throw new ForbiddenException('Anonymization is not allowed');
		}

		// Only possible, if user is allowed to deanonymize
		$this->poll->request(Poll::PERMISSION_DEANONYMIZE);

		$this->poll->setAnonymous(-1);
		$this->poll = $this->pollMapper->update($this->poll);

		$this->eventDispatcher->dispatchTyped(new PollUpdatedEvent($this->poll));

		return $this->poll;
	}

	/**
	 * Update timestamp for last interaction with polls
	 */
	public function setLastInteraction(int $pollId): void {
		if ($pollId) {
			$this->pollMapper->setLastInteraction($pollId);
		}
	}

	/**
	 * Move to archive or restore
	 * @return Poll
	 */
	public function toggleArchive(int $pollId): Poll {
		$this->poll = $this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_DELETE);

		$this->poll->setDeleted($this->poll->getDeleted() ? 0 : time());
		$this->poll = $this->pollMapper->update($this->poll);

		if ($this->poll->getDeleted()) {
			$this->eventDispatcher->dispatchTyped(new PollArchivedEvent($this->poll));
		} else {
			$this->eventDispatcher->dispatchTyped(new PollRestoredEvent($this->poll));
		}

		return $this->poll;
	}

	/**
	 * Delete poll
	 * @return Poll
	 */
	public function delete(int $pollId): Poll {
		try {
			$this->poll = $this->pollMapper->get($pollId)
				->request(Poll::PERMISSION_POLL_DELETE);
		} catch (DoesNotExistException $e) {
			throw new AlreadyDeletedException('Poll not found, assume already deleted');
		}

		$this->eventDispatcher->dispatchTyped(new PollDeletedEvent($this->poll));

		$this->pollMapper->delete($this->poll);
		return $this->poll;
	}

	/**
	 * Close poll
	 * @return Poll
	 */
	public function close(int $pollId): Poll {
		$this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_EDIT);
		return $this->toggleClose($pollId, time() - 5);
	}

	/**
	 * Reopen poll
	 * @return Poll
	 */
	public function reopen(int $pollId): Poll {
		$this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_EDIT);
		return $this->toggleClose($pollId, 0);
	}

	/**
	 * Close poll
	 * @return Poll
	 */
	private function toggleClose(int $pollId, int $expiry): Poll {
		$this->poll = $this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_EDIT);

		$this->poll->setExpire($expiry);
		if ($expiry > 0) {
			$this->eventDispatcher->dispatchTyped(new PollCloseEvent($this->poll));
		} else {
			$this->eventDispatcher->dispatchTyped(new PollReopenEvent($this->poll));
		}

		$this->poll = $this->pollMapper->update($this->poll);

		return $this->poll;
	}

	/**
	 * Clone poll
	 * @return Poll
	 */
	public function clone(int $pollId): Poll {
		$origin = $this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_ACCESS);
		$this->appSettings->getPollCreationAllowed();

		$this->poll = new Poll();
		$this->poll->setCreated(time());
		$this->poll->setOwner($this->userSession->getCurrentUserId());
		$this->poll->setTitle('Clone of ' . $origin->getTitle());
		$this->poll->setDeleted(0);
		$this->poll->setAccess(Poll::ACCESS_PRIVATE);

		$this->poll->setType($origin->getType());
		$this->poll->setVotingVariant($origin->getVotingVariant());
		$this->poll->setDescription($origin->getDescription());
		$this->poll->setExpire($origin->getExpire());
		// deanonymize cloned polls by default, to avoid locked anonymous polls
		$this->poll->setAnonymous(0);
		$this->poll->setAllowMaybe($origin->getAllowMaybe());
		$this->poll->setVoteLimit($origin->getVoteLimit());
		$this->poll->setShowResults($origin->getShowResults());
		$this->poll->setAdminAccess($origin->getAdminAccess());

		$this->poll = $this->pollMapper->insert($this->poll);
		$this->eventDispatcher->dispatchTyped(new PollCreatedEvent($this->poll));
		return $this->poll;
	}

	/**
	 * Collect email addresses from particitipants
	 *
	 */
	public function getParticipantsEmailAddresses(int $pollId): array {
		$this->poll = $this->pollMapper->get($pollId)
			->request(Poll::PERMISSION_POLL_EDIT);

		$votes = $this->voteMapper->findParticipantsByPoll($this->poll->getId());
		$list = [];
		foreach ($votes as $vote) {
			$user = $vote->getUser();
			$list[] = [
				'displayName' => $user->getDisplayName(),
				'emailAddress' => $user->getEmailAddress(),
				'combined' => $user->getEmailAndDisplayName(),
			];
		}
		return $list;
	}

	/**
	 * Get valid values for configuration options
	 *
	 * @return array
	 *
	 * @psalm-return array{pollType: mixed, access: mixed, showResults: mixed}
	 */
	public function getValidEnum(): array {
		return [
			'pollType' => $this->getValidPollType(),
			'access' => $this->getValidAccess(),
			'showResults' => $this->getValidShowResults()
		];
	}

	/**
	 * Get valid values for pollType
	 *
	 * @return string[]
	 *
	 * @psalm-return array{0: string, 1: string}
	 */
	private function getValidPollType(): array {
		return [Poll::TYPE_DATE, Poll::TYPE_TEXT];
	}

	/**
	 * Get valid values for access
	 *
	 * @return string[]
	 *
	 * @psalm-return array{0: string, 1: string}
	 */
	private function getValidAccess(): array {
		return [Poll::ACCESS_PRIVATE, Poll::ACCESS_OPEN];
	}

	/**
	 * Get valid values for showResult
	 *
	 * @return string[]
	 *
	 * @psalm-return array{0: string, 1: string, 2: string}
	 */
	private function getValidShowResults(): array {
		return [Poll::SHOW_RESULTS_ALWAYS, Poll::SHOW_RESULTS_CLOSED, Poll::SHOW_RESULTS_NEVER];
	}
}
