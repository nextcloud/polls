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

use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Model\User\Admin;
use OCA\Polls\Model\Group\Circle;
use OCA\Polls\Model\Group\ContactGroup;
use OCA\Polls\Model\User\Contact;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\GenericUser;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\User\User;
use OCA\Polls\Model\UserBase;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Share\IShare;

class UserService {
	/** @var ShareMapper */
	private $shareMapper;
	
	/** @var ISession */
	private $session;

	/** @var IFactory */
	private $transFactory;

	/** @var IUserManager */
	private $userManager;

	/** @var IUserSession */
	private $userSession;

	/** @var ISearch */
	private $userSearch;
	
	/** @var VoteMapper */
	private $voteMapper;
	
	public function __construct(
		IFactory $transFactory,
		ISearch $userSearch,
		ISession $session,
		IUserSession $userSession,
		IUserManager $userManager,
		ShareMapper $shareMapper,
		VoteMapper $voteMapper
	) {
		$this->transFactory = $transFactory;
		$this->userSearch = $userSearch;
		$this->session = $session;
		$this->shareMapper = $shareMapper;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
		$this->voteMapper = $voteMapper;
	}

	/**
	 * getCurrentUser - Get current user from userbase or from share in public polls
	 * @return Admin|Circle|Contact|ContactGroup|Email|GenericUser|Group|User
	 */

	public function getCurrentUser() : UserBase {
		// If there is a valid user session, get current user from session
		if ($this->userSession->getUser()) {
			return new User($this->userSession->getUser()->getUID());
		}
		
		// Retrieve token and get current user from share
		$token = $this->session->get('publicPollToken');

		if ($token) {
			$share = $this->shareMapper->findByToken($token);
			return $this->getUserFromShare($share);
		}

		throw new DoesNotExistException('User not found');
	}

	/**
	 * find appropriate language
	 */

	public function getGenericLanguage() {
		return $this->transFactory->findGenericLanguage('polls');
	}

	/**
	 * evaluateUser - Get user by name and poll in case of a share user of the particulair poll
	 */

	public function evaluateUser(string $userId, int $pollId = 0): ?UserBase {
		// if a user with this name exists, return from the user base
		$user = $this->userManager->get($userId);
		if ($user) {
			return new User($userId);
		}
		// Otherwise get it from a share that belongs to the poll and return the share user
		try {
			$share = $this->shareMapper->findByPollAndUser($pollId, $userId);
			return $this->getUserFromShare($share);
		} catch (Exception $e) {
			return null;
		}
	}

	/**
	 * Get participans of a poll as array of user objects
	 * @return array<array-key, mixed>
	 */
	public function getParticipants($pollId) : array {
		$users = [];
		// get the distict list of usernames from the votes
		$participants = $this->voteMapper->findParticipantsByPoll($pollId);

		foreach ($participants as &$participant) {
			$user = $this->evaluateUser($participant->getUserId(), $pollId);
			if ($user) {
				// replace every entry with a user object
				$users[] = $this->evaluateUser($participant->getUserId(), $pollId);
			}
		}
		return $users;
	}

	/**
	 * Create user from share
	 * @return Admin|Circle|Contact|ContactGroup|Email|GenericUser|Group|User
	 */
	public function getUserFromShare(Share $share) : UserBase {
		return $this->getUser(
			$share->getType(),
			$share->getUserId(),
			$share->getDisplayName(),
			$share->getEmailAddress(),
			$share->getLanguage(),
			$share->getLocale(),
			$share->getTimeZoneName()
		);
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
			$items[] = new User($item['value']['shareWith']);
		}

		foreach (($result['exact']['users'] ?? []) as $item) {
			$items[] = new User($item['value']['shareWith']);
		}

		foreach (($result['groups'] ?? []) as $item) {
			$items[] = new Group($item['value']['shareWith']);
		}

		foreach (($result['exact']['groups'] ?? []) as $item) {
			$items[] = new Group($item['value']['shareWith']);
		}

		$items = array_merge($items, Contact::search($query));
		$items = array_merge($items, ContactGroup::search($query));

		if (Circle::isEnabled()) {
			foreach (($result['circles'] ?? []) as $item) {
				$items[] = new Circle($item['value']['shareWith']);
			}
			foreach (($result['exact']['circles'] ?? []) as $item) {
				$items[] = new Circle($item['value']['shareWith']);
			}
		}

		return $items;
	}

	/**
	 * create a new user object based on $type
	 * @return Circle|Contact|ContactGroup|Email|GenericUser|Group|User|Admin
	 */
	public function getUser(string $type, string $id, string $displayName = '', string $emailAddress = '', ?string $language = '', string $locale = '', string $timeZoneName = ''): UserBase {
		switch ($type) {
			case Group::TYPE:
				return new Group($id);
			case Circle::TYPE:
				return new Circle($id);
			case Contact::TYPE:
				return new Contact($id);
			case ContactGroup::TYPE:
				return new ContactGroup($id);
			case User::TYPE:
				return new User($id);
			case Admin::TYPE:
				return new Admin($id);
			case Email::TYPE:
				return new Email($id, $displayName, $emailAddress, $language);
			case UserBase::TYPE_PUBLIC:
				return new GenericUser($id, UserBase::TYPE_PUBLIC);
			case UserBase::TYPE_EXTERNAL:
				return new GenericUser($id, UserBase::TYPE_EXTERNAL, $displayName, $emailAddress, $language, $locale, $timeZoneName);
			default:
				throw new InvalidShareTypeException('Invalid user type (' . $type . ')');
		}
	}
}
