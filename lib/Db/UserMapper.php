<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Db;

use Exception;
use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Exceptions\ShareNotFoundException;
use OCA\Polls\Exceptions\UserNotFoundException;
use OCA\Polls\Model\Group\Circle;
use OCA\Polls\Model\Group\ContactGroup;
use OCA\Polls\Model\Group\Group;
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

	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
	public function __construct(
		IDBConnection $db,
		protected IUserManager $userManager,
	) {
		parent::__construct($db, Share::TABLE, Share::class);
	}

	/**
	 * Get poll participant
	 *
	 * Returns a UserBase child from share determined by userId and pollId
	 *
	 * @param string $userId Get internal user. If pollId is given, the user who participates in the particulair poll will be returned
	 * @param int $pollId Can only be used together with $userId and will return the internal user or the share user
	 * @return UserBase
	 **/
	public function getParticipant(string $userId, int $pollId): UserBase {
		if ($userId === '') {
			return new UserBase($userId, UserBase::TYPE_EMPTY);
		}

		try {
			return $this->getUserFromUserBase($userId, $pollId);
		} catch (UserNotFoundException $e) {
			// just catch and continue if not found and try to find user by share;
		}

		try {
			$share = $this->getShareByPollAndUser($userId, $pollId);
			return $this->getUserFromShare($share);
		} catch (ShareNotFoundException $e) {
			// User seems to be probaly deleted, use fake share
			return new Ghost($userId);
		}
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
			$users[] = $this->getParticipant($participant->getUserId(), $pollId);
		}
		return $users;
	}

	public function getUserFromUserBase(string $userId, ?int $pollId = null): User {
		$user = $this->userManager->get($userId);
		if ($user instanceof IUser) {
			try {
				// check if we find a share, where the user got admin rights for the particular poll
				if ($pollId !== null && $this->getShareByPollAndUser($userId, $pollId)->getType() === Share::TYPE_ADMIN) {
					return new Admin($userId);
				}
			} catch (Exception $e) {
				// silent catch
			}
			return new User($userId);
		}
		throw new UserNotFoundException();
	}

	/**
	 * Get participans of a poll as array of user objects
	 *
	 * Returns a UserBase child build from a share
	 *
	 * @return Circle|Contact|ContactGroup|Email|GenericUser|Ghost|Group|User
	 */
	public function getUserFromShare(Share $share): GenericUser|Email|User|ContactGroup|Contact|Circle|Group|Ghost {
		return $this->getUserObject(
			$share->getType(),
			$share->getUserId(),
			$share->getDisplayName(),
			$share->getEmailAddress(),
			$share->getLanguage(),
			$share->getLocale(),
			$share->getTimeZoneName()
		);
	}

	public function getUserFromShareToken(string $token): UserBase {
		$share = $this->getShareByToken($token);

		return $this->getUserFromShare($share);
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
		} catch (DoesNotExistException $e) {
			throw new ShareNotFoundException('Share not found by userId and pollId');
		}
	}

	/**
	 * @throws InvalidShareTypeException
	 */
	public function getUserObject(string $type, string $id, string $displayName = '', string $emailAddress = '', string $language = '', string $locale = '', string $timeZoneName = ''): Ghost|Group|Circle|Contact|ContactGroup|User|Email|GenericUser {
		return match ($type) {
			Ghost::TYPE => new Ghost($id),
			Group::TYPE => new Group($id),
			Circle::TYPE => new Circle($id),
			Contact::TYPE => new Contact($id),
			ContactGroup::TYPE => new ContactGroup($id),
			User::TYPE => new User($id),
			Admin::TYPE => new Admin($id),
			Email::TYPE => new Email($id, $displayName, $emailAddress, $language),
			UserBase::TYPE_EXTERNAL => new GenericUser($id, UserBase::TYPE_EXTERNAL, $displayName, $emailAddress, $language, $locale, $timeZoneName),
			UserBase::TYPE_PUBLIC => new GenericUser($id, UserBase::TYPE_PUBLIC, $displayName),
			default => throw new InvalidShareTypeException('Invalid user type (' . $type . ')'),
		};
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
