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

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\InvalidShareType;
use OCA\Polls\Exceptions\ShareAlreadyExistsException;
use OCA\Polls\Exceptions\NotFoundException;

use OCP\Security\ISecureRandom;

use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Model\Acl;
use OCA\Polls\Model\UserGroupClass;

class ShareService {

	/** @var SystemService */
	private $systemService;

	/** @var ShareMapper */
	private $shareMapper;

	/** @var Share */
	private $share;

	/** @var MailService */
	private $mailService;

	/** @var Acl */
	private $acl;

	/**
	 * ShareController constructor.
	 * @param SystemService $systemService
	 * @param ShareMapper $shareMapper
	 * @param Share $share
	 * @param MailService $mailService
	 * @param Acl $acl
	 */
	public function __construct(
		SystemService $systemService,
		ShareMapper $shareMapper,
		Share $share,
		MailService $mailService,
		Acl $acl
	) {
		$this->systemService = $systemService;
		$this->shareMapper = $shareMapper;
		$this->share = $share;
		$this->mailService = $mailService;
		$this->acl = $acl;
	}

	/**
	 * Read all shares of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return array array of Share
	 * @throws NotAuthorizedException
	 * @throws NotFoundException
	 */
	public function list($pollId) {
		if (!$this->acl->set($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		try {
			$shares = $this->shareMapper->findByPoll($pollId);
		} catch (DoesNotExistException $e) {
			return [];
		}

		return $shares;
	}

	/**
	 * Get share by token
	 * @NoAdminRequired
	 * @param string $token
	 * @return Share
	 * @throws NotFoundException
	 */
	public function get($token) {
		try {
			$this->share = $this->shareMapper->findByToken($token);
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}
		// Allow users entering the poll with a public share access

		if ($this->share->getType() === Share::TYPE_PUBLIC && \OC::$server->getUserSession()->isLoggedIn()) {
			// Check if the user has already access
			if (!$this->acl->set($this->share->getPollId())->getAllowView()) {

				// Create a new share for this user, so he is allowed to access the poll later
				// via normal shared access and return the created share
				return $this->create(
					$this->share->getPollId(),
					UserGroupClass::getUserGroupChild(Share::TYPE_USER, \OC::$server->getUserSession()->getUser()->getUID()),
					true
				);
			}
		}
		return $this->share;
	}

	/**
	 * Get share by token
	 * @NoAdminRequired
	 * @param string $token
	 * @return Share
	 */
	public function setInvitationSent($token) {
		$share = $this->get($token);
		$share->setInvitationSent(time());
		return $this->shareMapper->update($share);
	}

	/**
	 * crate share
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param UserGroupClass $userGroup
	 * @param bool $skipInvitation
	 * @return Share
	 */
	private function create($pollId, $userGroup, $skipInvitation = false) {
		$this->share = new Share();
		$this->share->setToken(\OC::$server->getSecureRandom()->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		));
		$this->share->setPollId($pollId);
		$this->share->setInvitationSent($skipInvitation ? time() : 0);
		$this->share->setType($userGroup->getType());
		$this->share->setUserId($userGroup->getPublicId());
		$this->share->setDisplayName($userGroup->getDisplayName());
		$this->share->setEmailAddress($userGroup->getEmailAddress());

		return $this->shareMapper->insert($this->share);
	}

	/**
	 * Add share
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param array $user
	 * @return Share
	 * @throws NotAuthorizedException
	 * @throws InvalidShareType
	 */
	public function add($pollId, $type, $userId = '') {
		if (!$this->acl->set($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		if ($type !== UserGroupClass::TYPE_PUBLIC) {
			try {
				$this->shareMapper->findByPollAndUser($pollId, $userId);
				throw new ShareAlreadyExistsException;
			} catch (MultipleObjectsReturnedException $e) {
				throw new ShareAlreadyExistsException;
			} catch (DoesNotExistException $e) {
				// continue
			}
		}

		$userGroup = UserGroupClass::getUserGroupChild($type, $userId);
		return $this->create($pollId, $userGroup);
	}

	/**
	 * Set emailAddress to personal share
	 * or update an email share with the username
	 * @NoAdminRequired
	 * @param string $token
	 * @param string $emailAddress
	 * @return Share
	 * @throws InvalidShareType
	 * @throws NotFoundException
	 */
	public function setEmailAddress($token, $emailAddress) {
		try {
			$this->share = $this->shareMapper->findByToken($token);
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}

		if ($this->share->getType() === Share::TYPE_EXTERNAL) {
			$this->systemService->validateEmailAddress($emailAddress);
			$this->share->setEmailAddress($emailAddress);
			// TODO: Send confirmation
			return $this->shareMapper->update($this->share);
		} else {
			throw new InvalidShareType('Email address can only be set in external shares.');
		}
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 * @NoAdminRequired
	 * @param string $token
	 * @param string $userName
	 * @return Share
	 * @throws NotAuthorizedException
	 * @throws NotFoundException
	 */
	public function personal($token, $userName, $emailAddress = '') {
		try {
			$this->share = $this->shareMapper->findByToken($token);
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}

		$this->systemService->validatePublicUsername($userName, $token);
		$this->systemService->validateEmailAddress($emailAddress, true);

		if ($this->share->getType() === Share::TYPE_PUBLIC) {
			// Create new external share for user, who entered the poll via public link
			$this->create(
				$this->share->getPollId(),
				UserGroupClass::getUserGroupChild(Share::TYPE_EXTERNAL, $userName, $userName, $emailAddress));
			if ($emailAddress) {
				$this->mailService->sendInvitation($this->share->getToken());
			}

			return $this->share;

		} elseif ($this->share->getType() === Share::TYPE_EMAIL
				|| $this->share->getType() === Share::TYPE_CONTACT) {
			// Convert Email and contact shares to external share, if user registeres
			$this->share->setType(Share::TYPE_EXTERNAL);
			$this->share->setUserId($userName);
			$this->share->setDisplayName($userName);
			$this->share->setEmailAddress($emailAddress);
			return $this->shareMapper->update($this->share);
		} else {
			throw new NotAuthorizedException;
		}
	}

	/**
	 * Delete share
	 * remove share
	 * @NoAdminRequired
	 * @param string $token
	 * @return Share
	 * @throws NotAuthorizedException
	 */

	public function delete($token) {
		try {
			$this->share = $this->shareMapper->findByToken($token);
			if (!$this->acl->set($this->share->getPollId())->getAllowEdit()) {
				throw new NotAuthorizedException;
			}
			$this->shareMapper->delete($this->share);
		} catch (DoesNotExistException $e) {
			// silently catch
		}
		return $token;
	}
}
