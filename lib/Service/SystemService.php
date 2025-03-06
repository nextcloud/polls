<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Service;

use Exception;
use OCA\Polls\AppConstants;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Exceptions\InvalidUsernameException;
use OCA\Polls\Exceptions\TooShortException;
use OCA\Polls\Model\Acl;
use OCA\Polls\Model\Group\Circle;
use OCA\Polls\Model\Group\ContactGroup;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\User\Contact;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\User;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\L10N\IFactory;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class SystemService {
	private const REGEX_INVALID_USERNAME_CHARACTERS = '/[\x{2000}-\x{206F}]/u';
	/**
	 * @psalm-suppress PossiblyUnusedMethod
	 */
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
			try {
				// try to identify an email address
				$result = MailService::extractEmailAddressAndName($query);
				$list[] = new Email($result['emailAddress'], $result['displayName'], $result['emailAddress']);
			} catch (Exception $e) {
				// catch silent
			}
			// search more matches in circles, users, groups and contacts
			$list = array_merge($list, $this->search($query));
		}

		return $list;
	}

	/**
	 * get a list of user objects from the backend matching the query string
	 */
	public function search(string $query = ''): array {
		$startSearchTimer = microtime(true);
		$items = [];
		$types = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_EMAIL
		];
		$maxResults = 200;
		if (Circle::isEnabled() && class_exists('\OCA\Circles\ShareByCircleProvider')) {
			// Add circles to the search, if app is enabled
			$types[] = IShare::TYPE_CIRCLE;
		}
		$startCollaborationSearchTimer = microtime(true);
		[$result, $more] = $this->userSearch->search($query, $types, false, $maxResults, 0);
		$this->logger->debug('Search took {time}s', ['time' => microtime(true) - $startCollaborationSearchTimer]);

		if ($more) {
			$this->logger->info('Only first {maxResults} matches will be returned.', ['maxResults' => $maxResults]);
		}

		foreach (($result['users'] ?? []) as $item) {
			if (isset($item['value']['shareWith'])) {
				$items[] = $this->userMapper->getUserFromUserBase($item['value']['shareWith'])->getRichUserArray();
			} else {
				$this->handleFailedSearchResult($query, $item, 'users');
			}
		}

		foreach (($result['exact']['users'] ?? []) as $item) {
			if (isset($item['value']['shareWith'])) {
				$items[] = $this->userMapper->getUserFromUserBase($item['value']['shareWith'])->getRichUserArray();
			} else {
				$this->handleFailedSearchResult($query, $item, 'exact users');
			}
		}

		foreach (($result['groups'] ?? []) as $item) {
			if (isset($item['value']['shareWith'])) {
				$items[] = (new Group($item['value']['shareWith']))->getRichUserArray();
			} else {
				$this->handleFailedSearchResult($query, $item, 'groups');
			}
		}

		foreach (($result['exact']['groups'] ?? []) as $item) {
			if (isset($item['value']['shareWith'])) {
				$items[] = (new Group($item['value']['shareWith']))->getRichUserArray();
			} else {
				$this->handleFailedSearchResult($query, $item, 'exact groups');
			}
		}

		if (Contact::isEnabled()) {
			foreach (Contact::search($query) as $contact) {
				$items[] = $contact->getRichUserArray();
			}
			foreach (ContactGroup::search($query) as $contact) {
				$items[] = $contact->getRichUserArray();
			}
			// $items = array_merge($items, Contact::search($query));
			// $items = array_merge($items, ContactGroup::search($query));
		}

		if (Circle::isEnabled()) {
			foreach (($result['circles'] ?? []) as $item) {
				$items[] = $this->userMapper->getUserObject(Circle::TYPE, $item['value']['shareWith'])->getRichUserArray();
			}

			foreach (($result['exact']['circles'] ?? []) as $item) {
				$items[] = $this->userMapper->getUserObject(Circle::TYPE, $item['value']['shareWith'])->getRichUserArray();
			}
		}
		$this->logger->debug('Search took {time}s overall', ['time' => microtime(true) - $startSearchTimer]);
		return $items;
	}

	private function handleFailedSearchResult(string $query, mixed $item, string $type = 'unspecified'): void {
		$this->logger->debug('Unrecognized search result', [
			'query' => $query,
			'result' => json_encode($item),
			'type' => $type,
		]);
	}

	/**
	 * find appropriate language
	 */
	public function getGenericLanguage(): string {
		return $this->transFactory->findGenericLanguage(AppConstants::APP_ID);
	}

	/**
	 * Validate if the user name is reserved
	 * return false, if the requested userId or displayName exists as a user or as
	 * a participant of refenced poll
	 * The check spans over userId and displayName
	 * @param string $userName displayName or userId to check for existance
	 * @param string $token the share referencing the poll
	 * @return string returns the allowed username
	 */
	public function validatePublicUsernameByToken(string $userName, string $token): string {
		$share = $this->shareMapper->findByToken($token);
		return $this->validatePublicUsername($userName, $share);
	}

	/**
	 * Validate if the user name is reserved
	 * return false, if the requested userId or displayName exists as a user or as
	 * a participant of refenced poll
	 * The check spans over userId and displayName
	 * @param string $userName displayName or userId to check for existance
	 * @param Share $share the share referencing the poll
	 * @return string returns the allowed username
	 */
	public function validatePublicUsername(string $userName, Share $share): string {
		$userName = trim(preg_replace(self::REGEX_INVALID_USERNAME_CHARACTERS, '', $userName));

		if (!$userName) {
			throw new TooShortException('Username must not be empty');
		}

		if ($share->getDisplayName() === $userName) {
			return $userName;
		}

		$compareUserName = strtolower($userName);

		// reserved usernames
		if (str_contains($compareUserName, 'deleted user') || str_contains($compareUserName, 'anonymous')) {
			throw new InvalidUsernameException;
		}

		// get all groups, that include the requested username in their gid
		// or displayname and check if any match completely
		foreach (Group::search($compareUserName) as $group) {
			if ($group->hasName($compareUserName)) {
				throw new InvalidUsernameException;
			}
		}

		// get all users
		foreach (User::search($compareUserName) as $user) {
			if ($user->hasName($compareUserName)) {
				throw new InvalidUsernameException;
			}
		}

		// get all participants
		foreach ($this->voteMapper->findParticipantsByPoll($share->getPollId()) as $vote) {
			if ($vote->getUser()->hasName($compareUserName)) {
				throw new InvalidUsernameException;
			}
		}

		// get all shares for this poll
		foreach ($this->shareMapper->findByPoll($share->getPollId()) as $share) {
			if ($share->getType() !== Circle::TYPE && $share->getUser()->hasName($compareUserName)) {
				throw new InvalidUsernameException;
			}
		}
		// return $userName, if username is allowed
		return $userName;
	}
}
