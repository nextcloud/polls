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
use OCA\Polls\Exceptions\InvalidShareTypeException;
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
	 * 	 * Read all shares of a poll based on the poll id and return list as array
	 *
	 * @return Share[]
	 *
	 * @psalm-return array<array-key, Share>
	 */
	public function list(int $pollId): array {
		$this->acl->setPollId($pollId)->requestEdit();

		try {
			$shares = $this->shareMapper->findByPoll($pollId);
		} catch (DoesNotExistException $e) {
			return [];
		}

		return $shares;
	}

	/**
	 * Get share by token
	 */
	public function get(string $token) {
		try {
			$this->share = $this->shareMapper->findByToken($token);
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}
		// Allow users entering the poll with a public share access

		if ($this->share->getType() === Share::TYPE_PUBLIC && \OC::$server->getUserSession()->isLoggedIn()) {
			try {
				// Test if the user has already access.
				$this->acl->setPollId($this->share->getPollId());
			} catch (NotAuthorizedException $e) {
				// If he is not authorized until now, create a new personal share for this user.
				// Return the created share
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
	 * 	 * Get share by token
	 *
	 * @return Share
	 */
	public function setInvitationSent(string $token): Share {
		$share = $this->get($token);
		$share->setInvitationSent(time());
		return $this->shareMapper->update($share);
	}

	/**
	 * 	 * crate share - MUST BE PRIVATE!
	 *   * convert type contact to type email
	 *
	 * @return Share
	 */
	private function create(int $pollId, UserGroupClass $userGroup, bool $preventInvitation = false): Share {
		$this->share = new Share();
		$this->share->setToken(\OC::$server->getSecureRandom()->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		));
		$this->share->setPollId($pollId);
		$this->share->setInvitationSent($preventInvitation ? time() : 0);
		$this->share->setDisplayName($userGroup->getDisplayName());
		$this->share->setEmailAddress($userGroup->getEmailAddress());

		// Convert user type contact to share type email
		if ($userGroup->getType() === Share::TYPE_CONTACT) {
			$this->share->setType(UserGroupClass::TYPE_EMAIL);
			$this->share->setUserId($userGroup->getEmailAddress());
		} else {
			$this->share->setType($userGroup->getType());
			$this->share->setUserId($userGroup->getPublicId());
		}

		return $this->shareMapper->insert($this->share);
	}

	/**
	 * 	 * Add share
	 *
	 * @return Share
	 */
	public function add(int $pollId, string $type, string $userId = ''): Share {
		$this->acl->setPollId($pollId)->requestEdit();

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
	 * 	 * Set emailAddress to personal share
	 * 	 * or update an email share with the username
	 *
	 * @return Share
	 */
	public function setEmailAddress(string $token, string $emailAddress): Share {
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
			throw new InvalidShareTypeException('Email address can only be set in external shares.');
		}
	}

	/**
	 * 	 * Create a personal share from a public share
	 * 	 * or update an email share with the username
	 *
	 * @return Share
	 */
	public function personal(string $token, string $userName, string $emailAddress = ''): Share {
		try {
			$this->share = $this->shareMapper->findByToken($token);
		} catch (DoesNotExistException $e) {
			throw new NotFoundException('Token ' . $token . ' does not exist');
		}

		$this->systemService->validatePublicUsername($userName, $token);
		$this->systemService->validateEmailAddress($emailAddress, true);

		if ($this->share->getType() === Share::TYPE_PUBLIC) {
			// Create new external share for user, who entered the poll via public link,
			// prevent invtation sending, when no email address is given
			$this->create(
				$this->share->getPollId(),
				UserGroupClass::getUserGroupChild(Share::TYPE_EXTERNAL, $userName, $userName, $emailAddress),
				!$emailAddress
			);
		} elseif ($this->share->getType() === Share::TYPE_EMAIL
				|| $this->share->getType() === Share::TYPE_CONTACT) {
			// Convert email and contact shares to external share, if user registers
			$this->share->setType(Share::TYPE_EXTERNAL);
			$this->share->setUserId($userName);
			$this->share->setDisplayName($userName);

			// prepare for resending inviataion to new email address
			if ($emailAddress !== $this->share->getEmailAddress()) {
				$this->share->setInvitationSent(0);
			}
			$this->share->setEmailAddress($emailAddress);
			$this->shareMapper->update($this->share);
		} else {
			throw new NotAuthorizedException;
		}

		// send invitaitoin mail, if invitationSent has no timestamp
		if (!$this->share->getInvitationSent()) {
			$this->mailService->resendInvitation($this->share->getToken());
		}

		return $this->share;
	}

	/**
	 * Delete share
	 */
	public function delete(string $token): string {
		try {
			$this->share = $this->shareMapper->findByToken($token);
			$this->acl->setPollId($this->share->getPollId())->requestEdit();
			$this->shareMapper->delete($this->share);
		} catch (DoesNotExistException $e) {
			// silently catch
		}
		return $token;
	}
}
