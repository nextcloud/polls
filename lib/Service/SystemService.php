<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

namespace OCA\Polls\Service;

use OCA\Polls\Exceptions\TooShortException;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\InvalidEmailAddress;

use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Model\Circle;
use OCA\Polls\Model\Contact;
use OCA\Polls\Model\ContactGroup;
use OCA\Polls\Model\Email;
use OCA\Polls\Model\Group;
use OCA\Polls\Model\User;

class SystemService {

	/** @var VoteMapper */
	private $voteMapper;

	/** @var ShareMapper */
	private $shareMapper;

	public function __construct(
		VoteMapper $voteMapper,
		ShareMapper $shareMapper
	) {
		$this->voteMapper = $voteMapper;
		$this->shareMapper = $shareMapper;
	}

	/**
	 * 	 * Validate string as email address
	 *
	 * @return bool
	 */
	private static function isValidEmail(string $emailAddress): bool {
		return (!preg_match('/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/', $emailAddress)) ? false : true;
	}

	/**
	 * 	 * Validate email address and throw an exception
	 * 	 * return true, if email address is a valid
	 *
	 * @return true
	 */
	public static function validateEmailAddress(string $emailAddress, bool $emptyIsValid = false): bool {
		if (!$emailAddress && $emptyIsValid) {
			return true;
		} elseif (!self::isValidEmail($emailAddress)) {
			throw new InvalidEmailAddress;
		}
		return true;
	}

	/**
	 * Get a list of users
	 * @param string[] $skip
	 */
	public static function getSiteUsers(string $query = '', array $skip = []) {
		$users = [];
		foreach (\OC::$server->getUserManager()->searchDisplayName($query) as $user) {
			if (!in_array($user->getUID(), $skip) && $user->isEnabled()) {
				$users[] = new User($user->getUID());
			}
		}
		return $users;
	}

	/**
	 * 	 * Get a combined list of users, groups, circles, contact groups and contacts
	 *
	 * @return (Circle|Email|Group|User|Contact|ContactGroup|mixed)[]
	 *
	 * @psalm-return array<array-key, Circle|Email|Group|User|Contact|ContactGroup|mixed>
	 */
	public function getSiteUsersAndGroups(
		string $query = '',
		bool $getGroups = true,
		bool $getUsers = true,
		bool $getContacts = true,
		bool $getContactGroups = true,
		bool $getMail = true,
		array $skipGroups = [],
		array $skipUsers = []
	): array {
		$list = [];
		if ($query !== '') {
			if ($getMail && self::isValidEmail($query)) {
				$list[] = new Email($query);
			}

			if ($getGroups) {
				$list = array_merge($list, Group::search($query, $skipGroups));
			}

			if ($getUsers) {
				$list = array_merge($list, User::search($query, $skipUsers));
			}

			if ($getContacts) {
				$list = array_merge($list, Contact::search($query));
			}

			if ($getContactGroups) {
				$list = array_merge($list, ContactGroup::search($query));
			}
			$list = array_merge($list, Circle::search($query));
		}

		return $list;
	}

	/**
	 * 	 * Validate it the user name is reserved
	 * 	 * return false, if this username already exists as a user or as
	 * 	 * a participant of the poll
	 *
	 * @return true
	 */
	public function validatePublicUsername(string $userName, string $token): bool {
		$share = $this->shareMapper->findByToken($token);

		if ($share->getDisplayName() === $userName) {
			return true;
		}

		$userName = strtolower(trim($userName));

		// return forbidden, if the length of the userame is lower than 3 characters
		if (strlen($userName) < 3) {
			throw new TooShortException('Username must have at least 3 characters');
		}

		// get all groups
		foreach (Group::search() as $group) {
			if ($userName === strtolower(trim($group->getId()))
				|| $userName === strtolower(trim($group->getDisplayName()))) {
				throw new InvalidUsernameException;
			}
		}

		// get all users
		foreach (User::search() as $user) {
			if ($userName === strtolower(trim($user->getId()))
				|| $userName === strtolower(trim($user->getDisplayName()))) {
				throw new InvalidUsernameException;
			}
		}

		// get all participants
		foreach ($this->voteMapper->findParticipantsByPoll($share->getPollId()) as $vote) {
			if ($vote->getUserId()) {
				if ($userName === strtolower(trim($vote->getUserId()))) {
					throw new InvalidUsernameException;
				}
			}
		}

		// get all shares for this poll
		foreach ($this->shareMapper->findByPoll($share->getPollId()) as $share) {
			if ($share->getUserId() && $share->getType() !== Circle::TYPE) {
				if ($userName === strtolower(trim($share->getUserId()))
					|| $userName === strtolower(trim($share->getDisplayName()))) {
					throw new InvalidUsernameException;
				}
			}
		}
		// return true, if username is allowed
		return true;
	}
}
