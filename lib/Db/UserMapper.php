<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Exceptions\UserNotFoundException;
use OCA\Polls\Model\Group\ContactGroup;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\Group\Team;
use OCA\Polls\Model\User\Admin;
use OCA\Polls\Model\User\Contact;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\GenericUser;
use OCA\Polls\Model\User\Ghost;
use OCA\Polls\Model\User\User;
use OCA\Polls\Model\UserBase;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;

/**
 * @template-extends QBMapper<Share>
 *
 * This is a pseudo mapper for low level user operations to simplyfy unique handling of share users and nextcloud users
 */
class UserMapper extends QBMapper {
	public const TABLE = Share::TABLE;

	/** @var array<string, UserBase> */
	private static array $userCache = [];

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct(
		IDBConnection $db,
		protected IUserManager $userManager,
	) {
		parent::__construct($db, Share::TABLE, Share::class);
	}

	/**
	 * Get poll participant
	 *
	 * Returns a UserBase child from share determined by userId and pollId or from userbase by userId
	 *
	 * @param string $userId
	 * @param int|null $pollId Can only be used together with $userId and will return the internal user or the share user
	 * @return UserBase
	 **/
	public function getUser(string $userId, ?int $pollId = null): UserBase {
		if ($userId === '') {
			return new UserBase($userId, UserBase::TYPE_EMPTY);
		}

		try {
			return $this->getCachedUser($userId, $pollId);
		} catch (UserNotFoundException) {
			// Not found in cache, continue to fetch from userbase or share
		}

		try {
			$user = $this->getUserFromUserBase($userId);
			try {
				if ($pollId !== null && $this->getShareByPollAndUser($userId, $pollId)->getType() === Share::TYPE_ADMIN) {
					$user = new Admin($userId);
				}
			} catch (ShareNotFoundException) {
				// No admin share found
			}
		} catch (UserNotFoundException) {
			try {
				if ($pollId === null) {
					throw new ShareNotFoundException('PollId is required to get share user');
				}
				$user = $this->getShareByPollAndUser($userId, $pollId)->resolveUser();
			} catch (ShareNotFoundException) {
				$user = new Ghost($userId);
			}
		}

		$cacheKey = $userId . ':' . ($pollId ?? 'null');
		self::$userCache[$cacheKey] = $user;
		return $user;
	}

	/**
	 * Get participans of a poll as array of user objects
	 * @return UserBase[]
	 */
	public function getParticipants(int $pollId): array {
		$users = [];
		// get the distict list of usernames from the votes
		$participants = $this->findParticipantsByPoll($pollId);

		foreach ($participants as &$participant) {
			$users[] = $this->getUser($participant->getUserId(), $pollId);
		}
		return $users;
	}

	/**
	 * Get a user from the NC userbase
	 *
	 * @param string $userId
	 * @return User
	 * @throws UserNotFoundException
	 */
	public function getUserFromUserBase(string $userId): User {
		$user = $this->userManager->get($userId);
		if (!$user instanceof IUser) {
			throw new UserNotFoundException();
		}
		return new User($userId);
	}

	private function getCachedUser(string $userId, ?int $pollId = null): UserBase {
		$cacheKey = $userId . ':' . ($pollId ?? 'null');
		if (isset(self::$userCache[$cacheKey])) {
			return self::$userCache[$cacheKey];
		}
		throw new UserNotFoundException('User not found in cache');
	}


	public function getUserFromShareToken(string $token): UserBase {
		$share = $this->getShareByToken($token);

		return $share->resolveUser();
	}

	private function getShareByToken(string $token): Share {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('token', $qb->createNamedParameter($token, IQueryBuilder::PARAM_STR)));

		return $this->findEntity($qb);
	}

	/**
	 * @param string $userId
	 * @param int $pollId
	 * @return Share
	 * @throws ShareNotFoundException
	 */
	private function getShareByPollAndUser(string $userId, int $pollId): Share {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException) {
			throw new ShareNotFoundException('Share not found by userId and pollId');
		}
	}

	/**
	 * @throws InvalidShareTypeException
	 */
	public static function createUserObject(string $type, string $id, string $displayName = '', string $emailAddress = '', string $language = '', string $locale = '', string $timeZoneName = ''): Ghost|Group|Team|Contact|ContactGroup|User|Email|GenericUser {
		try {
			return match ($type) {
				UserBase::TYPE_GHOST => new Ghost($id),
				UserBase::TYPE_GROUP => new Group($id),
				UserBase::TYPE_TEAM => new Team($id),
				UserBase::TYPE_CONTACT => new Contact($id),
				UserBase::TYPE_CONTACTGROUP => new ContactGroup($id),
				UserBase::TYPE_USER => new User($id),
				UserBase::TYPE_ADMIN => new Admin($id),
				UserBase::TYPE_EMAIL => new Email($id, $displayName, $emailAddress, $language),
				UserBase::TYPE_EXTERNAL => new GenericUser($id, UserBase::TYPE_EXTERNAL, $displayName, $emailAddress, $language, $locale, $timeZoneName),
				UserBase::TYPE_PUBLIC => new GenericUser($id, UserBase::TYPE_PUBLIC, $displayName),
				default => throw new InvalidShareTypeException('Invalid user type (' . $type . ')'),
			};
		} catch (InvalidShareTypeException $e) {
			throw $e;
		} catch (\Exception) {
			return new Ghost($id);
		}
	}

	/**
	 * Get distinct participans as Vote of a poll
	 *
	 * @return Share[]
	 *
	 * @psalm-return array<Share>
	 */
	private function findParticipantsByPoll(int $pollId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->selectDistinct(['user_id', 'poll_id'])
			->from(Vote::TABLE)
			->where(
				$qb->expr()->eq('poll_id', $qb->createNamedParameter($pollId, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntities($qb);
	}
}
