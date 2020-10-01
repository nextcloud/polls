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

use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\TooShortException;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\InvalidEmailAddress;

use OCP\IGroupManager;
use OCP\IUserManager;
use OCA\Polls\Service\CirclesService;
use OCA\Polls\Service\ContactsService;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Model\User;

class SystemService {

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var CirclesService */
	private $circlesService;

	/** @var ContactsService */
	private $contactsService;

	/** @var VoteMapper */
	private $voteMapper;

	/** @var ShareMapper */
	private $shareMapper;

	/**
	 * SystemService constructor.
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param CirclesService $circlesService,
	 * @param ContactsService $contactsService,
	 * @param VoteMapper $voteMapper
	 * @param ShareMapper $shareMapper
	 */
	public function __construct(
		IGroupManager $groupManager,
		IUserManager $userManager,
		VoteMapper $voteMapper,
		CirclesService $circlesService,
		ContactsService $contactsService,
		ShareMapper $shareMapper
	) {
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->voteMapper = $voteMapper;
		$this->circlesService = $circlesService;
		$this->contactsService = $contactsService;
		$this->shareMapper = $shareMapper;
	}

	/**
	 * Validate string as email address
	 * @NoAdminRequired
	 * @param string $emailAddress
	 * @return bool
	 */
	private function isValidEmail($emailAddress) {
		return (!preg_match('/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/', $emailAddress)) ? false : true;
	}


	/**
	 * Get a list of users
	 * @NoAdminRequired
	 * @param string $query
	 * @param array $skip - usernames to skip in return array
	 * @return User[]
	 */
	public function getSiteUsers($query = '', $skip = []) {
		$users = [];
		foreach ($this->userManager->searchDisplayName($query) as $user) {
			if (!in_array($user->getUID(), $skip) && $user->isEnabled()) {
				$users[] = new User(User::TYPE_USER, $user->getUID());
			}
		}
		return $users;
	}

	/**
	 * Get a list of user groups
	 * @NoAdminRequired
	 * @param string $query
	 * @param array $skip - group names to skip in return array
	 * @return User[]
	 */
	public function getSiteGroups($query = '', $skip = []) {
		$groups = [];
		foreach ($this->groupManager->search($query) as $group) {
			if (!in_array($group->getGID(), $skip)) {
				$groups[] = new User(User::TYPE_GROUP, $group->getGID());
			}
		}
		return $groups;
	}

	/**
	 * Get a combined list of NC users, groups and contacts
	 * @NoAdminRequired
	 * @param string $query
	 * @param bool $getGroups - search in groups
	 * @param bool $getUsers - search in site users
	 * @param bool $getContacts - search in contacs
	 * @param bool $getContactGroups - search in contacs
	 * @param array $skipGroups - group names to skip in return array
	 * @param array $skipUsers - user names to skip in return array
	 * @return Array
	 */
	public function getSiteUsersAndGroups(
		$query = '',
		$getGroups = true,
		$getUsers = true,
		$getContacts = true,
		$getContactGroups = true,
		$getMail = false,
		$skipGroups = [],
		$skipUsers = []
	) {
		$list = [];
		if ($query !== '') {
			if ($getMail && $this->isValidEmail($query)) {
				$list[] = new User(User::TYPE_EMAIL, $query);
			}

			if ($getGroups) {
				$list = array_merge($list, $this->getSiteGroups($query, $skipGroups));
			}

			if ($getUsers) {
				$list = array_merge($list, $this->getSiteUsers($query, $skipUsers));
			}

			if ($getContacts) {
				$list = array_merge($list, $this->contactsService->getContacts($query));
			}

			if ($getContacts) {
				$list = array_merge($list, $this->contactsService->getContactsGroups($query));
			}
			$list = array_merge($list, $this->circlesService->getCircles($query));
		}

		return $list;
	}

	/**
	 * Validate it the user name is reservrd
	 * return false, if this username already exists as a user or as
	 * a participant of the poll
	 * @NoAdminRequired
	 * @return Boolean
	 * @throws InvalidEmailAddress
	 */
	public function validateEmailAddress($emailAddress) {
		if (!$this->isValidEmail($emailAddress)) {
			throw new InvalidEmailAddress;
		}
		return true;
	}


	/**
	 * Validate it the user name is reservrd
	 * return false, if this username already exists as a user or as
	 * a participant of the poll
	 * @NoAdminRequired
	 * @return Boolean
	 * @throws NotAuthorizedException
	 * @throws TooShortException
	 * @throws InvalidUsernameException
	 */
	public function validatePublicUsername($pollId, $userName, $token) {
		$userName = strtolower(trim($userName));

		// return forbidden, if $pollId does not match the share's pollId, force int compare
		if (intval($this->shareMapper->findByToken($token)->getPollId()) !== intVal($pollId)) {
			throw new NotAuthorizedException;
		}

		// return forbidden, if the length of the userame is lower than 3 characters
		if (strlen($userName) < 3) {
			return new TooShortException('Username must have at least 3 characters');
		}
		$list = [];

		// get all groups
		foreach ($this->getSiteGroups() as $user) {
			if (   $userName === strtolower(trim($user->getUserId()))
				|| $userName === strtolower(trim($user->getDisplayName()))) {
				throw new InvalidUsernameException;
			}
			$list[] = $user;
		}

		// get all users
		foreach ($this->getSiteUsers() as $user) {
			if (   $userName === strtolower(trim($user->getUserId()))
				|| $userName === strtolower(trim($user->getDisplayName()))) {
				throw new InvalidUsernameException;
			}
			$list[] = $user;
		}

		// get all participants
		foreach ($this->voteMapper->findParticipantsByPoll($pollId) as $vote) {
			if ($vote->getUserId() !== '' && $vote->getUserId() !== null) {
				$list[] = new User(User::TYPE_USER, $vote->getUserId());
				if (   $userName === strtolower(trim(end($list)->getUserId()))
					|| $userName === strtolower(trim(end($list)->getDisplayName()))) {
					throw new InvalidUsernameException;
				}
			}
		}

		// get all shares for this poll
		foreach ($this->shareMapper->findByPoll($pollId) as $share) {
			if (   $share->getUserId() !== ''
				&& $share->getUserId() !== null
			    && $share->getType() !== User::TYPE_CIRCLE) {
				$user = new User($share->getType(), $share->getUserId());
				\OC::$server->getLogger()->alert(json_encode($user));
				if (   $userName === strtolower(trim($user->getUserId()))
					|| $userName === strtolower(trim($share->getDisplayName()))
					|| $userName === strtolower(trim($user->getDisplayName()))) {
					throw new InvalidUsernameException;
				}
				$list[] = new User($share->getType(), $share->getUserId());
			}
		}
		// return true, if username is allowed
		return true;
	}
}
