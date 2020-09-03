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
use OCA\Polls\Exceptions\InvalidUsername;
use OCA\Polls\Exceptions\InvalidShareType;

use OCP\Security\ISecureRandom;

use OCA\Polls\Controller\SystemController;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Model\Acl;

class ShareService {

	/** @var SystemController */
	private $systemController;

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
	 * @param SystemController $systemController
	 * @param ShareMapper $shareMapper
	 * @param Share $share
	 * @param MailService $mailService
	 * @param Acl $acl
	 */
	public function __construct(
		SystemController $systemController,
		ShareMapper $shareMapper,
		Share $share,
		MailService $mailService,
		Acl $acl
	) {
		$this->systemController = $systemController;
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
	 * @param string $type
	 * @param string $userId
	 * @param string $userEmail
	 * @return Share
	 * @throws NotAuthorizedException
	 */
	public function add($pollId, $type, $userId, $userEmail = '') {
		if (!$this->acl->set($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$this->share = new Share();
		$this->share->setType($type);
		$this->share->setPollId($pollId);
		$this->share->setUserId($userId);
		$this->share->setUserEmail($userEmail);
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
	 * @throws NotAuthorizedException
	 */
	public function setEmailAddress($token, $emailAddress) {
		$this->share = $this->shareMapper->findByToken($token);
		if ($this->share->getType() === 'external') {
			// TODO: Simple validate email address
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
	 * @throws InvalidUsername
	 */
	public function personal($token, $userName, $emailAddress) {
		$this->share = $this->shareMapper->findByToken($token);

		// Return of validatePublicUsername is a DataResponse
		$checkUsername = $this->systemController->validatePublicUsername($this->share->getPollId(), $userName, $token);

		// if status is not 200, return DataResponse from validatePublicUsername
		if ($checkUsername->getStatus() !== 200) {
			throw new InvalidUsername;
		}

		if ($this->share->getType() === 'public') {
			$pollId = $this->share->getPollId();
			$this->share = new Share();
			$this->share->setToken(\OC::$server->getSecureRandom()->generate(
				16,
				ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
			));
			$this->share->setType('external');
			$this->share->setPollId($pollId);
			$this->share->setUserId($userName);
			$this->share->setUserEmail($emailAddress);
			$this->share->setInvitationSent(time());
			$this->shareMapper->insert($this->share);
			$this->mailService->sendInvitationMail($this->share->getToken());
			return $this->share;
		} elseif ($this->share->getType() === 'email') {
			$this->share->setType('external');
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
