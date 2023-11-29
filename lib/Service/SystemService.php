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

namespace OCA\Polls\Service;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Exceptions\InvalidEmailAddress;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\TooShortException;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\Group\Circle;
use OCA\Polls\Model\Group\ContactGroup;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\User\Contact;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\User;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class SystemService {
	private const REGEX_VALID_MAIL = '/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/';
	private const REGEX_PARSE_MAIL = '/(?:"?([^"]*)"?\s)?(?:<?(.+@[^>]+)>?)/';
	
	public function __construct(
		private IFactory $transFactory,
		private ISearch $userSearch,
		private LoggerInterface $logger,
		private ShareMapper $shareMapper,
		private VoteMapper $voteMapper,
		private UserMapper $userMapper,
	) {
	}

	/**
	 * Validate string as email address
	 *
	 * @return bool
	 */
	private static function isValidEmail(string $emailAddress): bool {
		return (!preg_match(self::REGEX_VALID_MAIL, $emailAddress)) ? false : true;
	}

	/**
	 * Validate email address and throw an exception
	 * return true, if email address is a valid
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
	 *
	 * @return User[]
	 */
	public static function getSiteUsers(string $query = '', array $skip = []): array {
		$users = [];
		foreach (Container::queryClass(IUserManager::class)->searchDisplayName($query) as $user) {
			if (!in_array($user->getUID(), $skip) && $user->isEnabled()) {
				$users[] = new User($user->getUID());
			}
		}
		return $users;
	}

	/**
	 * Get a list of groups
	 *
	 * @return Group[]
	 */
	public function getGroups(string $query = ''): array {
		$groups = Group::search($query);
		return $groups;
	}

	/**
	 * Get a combined list of users, groups, circles, contact groups and contacts
	 *
	 * @return (Circle|Email|Group|User|Contact|ContactGroup|mixed)[]
	 *
	 * @psalm-return array<array-key, Circle|Email|Group|User|Contact|ContactGroup|mixed>
	 */
	public function getSiteUsersAndGroups(string $query = ''): array {
		$list = [];
		if ($query !== '') {
			preg_match_all(self::REGEX_PARSE_MAIL, $query, $parsedQuery);

			$emailAddress = isset($parsedQuery[2][0]) ? $parsedQuery[2][0] : '';
			$displayName = isset($parsedQuery[1][0]) ? $parsedQuery[1][0] : '';
			if ($emailAddress && self::isValidEmail($emailAddress)) {
				$list[] = new Email($emailAddress, $displayName, $emailAddress);
			}

			$list = array_merge($list, $this->search($query));
		}

		return $list;
	}

	/**
	 * get a list of user objects from the backend matching the query string
	 */
	public function search(string $query = ''): array {
		$items = [];
		$types = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_EMAIL
		];
		if (Circle::isEnabled() && class_exists('\OCA\Circles\ShareByCircleProvider')) {
			// Add circles to the search, if app is enabled
			$types[] = IShare::TYPE_CIRCLE;
		}

		[$result, $more] = $this->userSearch->search($query, $types, false, 200, 0);

		foreach (($result['users'] ?? []) as $item) {
			if (isset($item['value']['shareWith'])) {
				$items[] = $this->userMapper->getUserFromUserBase($item['value']['shareWith']);
			} else {
				$this->handleFailedSearchResult($query, $item);
			}
		}

		foreach (($result['exact']['users'] ?? []) as $item) {
			if (isset($item['value']['shareWith'])) {
				$items[] = $this->userMapper->getUserFromUserBase($item['value']['shareWith']);
			} else {
				$this->handleFailedSearchResult($query, $item);
			}
		}

		foreach (($result['groups'] ?? []) as $item) {
			if (isset($item['value']['shareWith'])) {
				$items[] = new Group($item['value']['shareWith']);
			} else {
				$this->handleFailedSearchResult($query, $item);
			}
		}

		foreach (($result['exact']['groups'] ?? []) as $item) {
			if (isset($item['value']['shareWith'])) {
				$items[] = new Group($item['value']['shareWith']);
			} else {
				$this->handleFailedSearchResult($query, $item);
			}
		}

		if (Contact::isEnabled()) {
			$items = array_merge($items, Contact::search($query));
			$items = array_merge($items, ContactGroup::search($query));
		}

		if (Circle::isEnabled()) {
			foreach (($result['circles'] ?? []) as $item) {
				$items[] = $this->userMapper->getUserObject(Circle::TYPE, $item['value']['shareWith']);
			}

			foreach (($result['exact']['circles'] ?? []) as $item) {
				$items[] = $this->userMapper->getUserObject(Circle::TYPE, $item['value']['shareWith']);
			}
		}

		return $items;
	}

	private function handleFailedSearchResult(string $query, $item): void {
		$this->logger->debug('Unrecognized result for query: \"{query}\". Result: {result]', [
			'query' => $query,
			'result' => json_encode($item),
		]);
	}

	/**
	 * find appropriate language
	 */
	public function getGenericLanguage(): string {
		return $this->transFactory->findGenericLanguage(AppConstants::APP_ID);
	}


	/**
	 * Validate it the user name is reserved
	 * return false, if this username already exists as a user or as
	 * a participant of the poll
	 *
	 * @return true
	 */
	public function validatePublicUsername(string $userName, Share $share): bool {
		if (!$userName) {
			throw new TooShortException('Username must not be empty');
		}

		if ($share->getDisplayName() === $userName) {
			return true;
		}

		$userName = strtolower(trim($userName));

		// reserved usernames
		if (str_contains($userName, 'deleted user') || str_contains($userName, 'anonymous')) {
			throw new InvalidUsernameException;
		}

		// get all groups, that include the requested username in their gid
		// or displayname and check if any match completely
		foreach (Group::search($userName) as $group) {
			if ($userName === strtolower(trim($group->getId()))
				|| $userName === strtolower(trim($group->getDisplayName()))) {
				throw new InvalidUsernameException;
			}
		}

		// get all users
		foreach (User::search($userName) as $user) {
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
