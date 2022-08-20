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

use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Model\UserGroup\Admin;
use OCA\Polls\Model\UserGroup\Circle;
use OCA\Polls\Model\UserGroup\Contact;
use OCA\Polls\Model\UserGroup\ContactGroup;
use OCA\Polls\Model\UserGroup\Email;
use OCA\Polls\Model\UserGroup\GenericUser;
use OCA\Polls\Model\UserGroup\Group;
use OCA\Polls\Model\UserGroup\User;
use OCA\Polls\Model\UserGroup\UserBase;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IShare;

class UserService {
	/** @var ShareMapper */
	private $shareMapper;

	/** @var ISession */
	private $session;

	/** @var IUserManager */
	private $userManager;

	/** @var IUserSession */
	private $userSession;

	/** @var ISearch */
	private $userSearch;
	
	public function __construct(
		ISearch $userSearch,
		ISession $session,
		IUserSession $userSession,
		IUserManager $userManager,
		ShareMapper $shareMapper
	) {
		$this->userSearch = $userSearch;
		$this->session = $session;
		$this->shareMapper = $shareMapper;
		$this->userSession = $userSession;
		$this->userManager = $userManager;
	}

	/**
	 * getCurrentUser - Get current user from userbase or from share in public polls
	 */

	public function getCurrentUser() {
		if ($this->userSession->getUser()) {
			return $this->getUserFromShare($this->userSession->getUser()->getUID());
		}
		
		$token = $this->session->get('publicPollToken');

		if ($token) {
			return $this->getUserFromShare($token);
		}

		throw new DoesNotExistException('User not found');
	}

	/**
	 * evaluateUser - Get user by name; and poll in case of a share user of the particulair poll
	 */

	public function evaluateUser(string $userId, int $pollId = 0): ?UserBase {
		$user = $this->userManager->get($userId);
		if ($user) {
			return new User($userId);
		}
		try {
			$share = $this->shareMapper->findByPollAndUser($pollId, $userId);
			return $this->getUser(
				$share->getType(),
				$share->getUserId(),
				$share->getDisplayName(),
				$share->getEmailAddress()
			);
		} catch (Exception $e) {
			return null;
		}
	}

	/**
	 * Create user from share
	 * @return Admin|Circle|Contact|ContactGroup|Email|GenericUser|Group|User
	 */
	public function getUserFromShare(string $token) {
		$share = $this->shareMapper->findByToken($token);
		return $this->getUser(
			$share->getType(),
			$share->getUserId(),
			$share->getDisplayName(),
			$share->getEmailAddress()
		);
	}


	/**
	 * search all possible sharees - use ISearch to respect autocomplete restrictions
	 */
	public function search(string $query = ''): array {
		$items = [];
		$types = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_EMAIL
		];
		if (Circle::isEnabled() && class_exists('\OCA\Circles\ShareByCircleProvider')) {
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
	 * create a new user object
	 * @return Circle|Contact|ContactGroup|Email|GenericUser|Group|User|Admin
	 */
	public function getUser(string $type, string $id, string $displayName = '', string $emailAddress = ''): UserBase {
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
				return new Email($id, $displayName, $emailAddress);
			case UserBase::TYPE_PUBLIC:
				return new GenericUser($id, UserBase::TYPE_PUBLIC);
			case UserBase::TYPE_EXTERNAL:
				return new GenericUser($id, UserBase::TYPE_EXTERNAL, $displayName, $emailAddress);
			default:
				throw new InvalidShareTypeException('Invalid user type (' . $type . ')');
		}
	}
}
