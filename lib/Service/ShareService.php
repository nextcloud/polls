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
use OCA\Polls\Exceptions\InvalidShareType;

use OCP\Security\ISecureRandom;

use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Model\Acl;
use OCA\Polls\Model\User;

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
	 */
	public function list($pollId, $token) {
		if ($token) {
			return [$this->get($token)];
		}

		if (!$this->acl->set($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		return $this->shareMapper->findByPoll($pollId);
	}

	/**
	 * Get share by token
	 * @NoAdminRequired
	 * @param string $token
	 * @return Share
	 */
	public function get($token) {
		$this->share = $this->shareMapper->findByToken($token);
		return $this->share;
	}

	/**
	 * Add share
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param array $user
	 * @return Share
	 * @throws NotAuthorizedException
	 */
	public function add($pollId, $type, $userId, $emailAddress = '') {
		if (!$this->acl->set($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}
		$user = new User($type, $userId, $emailAddress);

		$this->share = new Share();
		$this->share->setPollId($pollId);
		$this->share->setType($user->getType());
		$this->share->setUserId($user->getUserId());
		$this->share->setDisplayName($user->getDisplayName());
		$this->share->setUserEmail($user->getEmailAddress());
		$this->share->setInvitationSent(0);
		$this->share->setToken(\OC::$server->getSecureRandom()->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		));

		return $this->shareMapper->insert($this->share);
	}

	/**
	 * Set emailAddress to personal share
	 * or update an email share with the username
	 * @NoAdminRequired
	 * @param string $token
	 * @param string $emailAddress
	 * @return Share
	 * @throws InvalidShareType
	 */
	public function setEmailAddress($token, $emailAddress) {
		$this->share = $this->shareMapper->findByToken($token);
		if ($this->share->getType() === Share::TYPE_EXTERNAL) {
			$this->systemService->validateEmailAddress($emailAddress);
			$this->share->setUserEmail($emailAddress);
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
	 */
	public function personal($token, $userName, $emailAddress = '') {
		$this->share = $this->shareMapper->findByToken($token);

		$this->systemService->validatePublicUsername($this->share->getPollId(), $userName, $token);

		if ($emailAddress) {
			$this->systemService->validateEmailAddress($emailAddress);
		}

		if ($this->share->getType() === Share::TYPE_PUBLIC) {
			$pollId = $this->share->getPollId();
			$this->share = new Share();
			$this->share->setToken(\OC::$server->getSecureRandom()->generate(
				16,
				ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
			));
			$this->share->setType(Share::TYPE_EXTERNAL);
			$this->share->setPollId($pollId);
			$this->share->setUserId($userName);
			$this->share->setUserEmail($emailAddress);
			$this->share->setInvitationSent(time());
			$this->shareMapper->insert($this->share);

			if ($emailAddress) {
				$this->mailService->sendInvitationMail($this->share->getToken());
			}

			return $this->share;
		} elseif ($this->share->getType() === Share::TYPE_EMAIL) {
			$this->share->setType(Share::TYPE_EXTERNAL);
			$this->share->setUserId($userName);
			$this->share->setUserEmail($emailAddress);
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
		$this->share = $this->shareMapper->findByToken($token);
		if (!$this->acl->set($this->share->getPollId())->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$this->shareMapper->delete($this->share);

		return $this->share;
	}
}
