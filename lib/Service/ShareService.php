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

use Exception;

use OCP\ILogger;
use OCP\Security\ISecureRandom;

use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\InvalidUsername;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Service\MailService;
use OCA\Polls\Model\Acl;
// TODO: Change to Service
use OCA\Polls\Controller\SystemController;

class ShareService {

	private $logger;
	private $acl;
	private $shareMapper;
	private $share;
	private $userId;

	private $pollMapper;
	private $systemController;
	private $mailService;

	/**
	 * ShareController constructor.
	 * @param string $appName
	 * @param string $userId
	 * @param IRequest $request
	 * @param ILogger $logger
	 * @param ShareMapper $shareMapper
	 * @param Share $share
	 * @param SystemController $systemController
	 * @param MailService $mailService
	 * @param Acl $acl
	 */
	public function __construct(
		string $appName,
		$userId,
		ILogger $logger,
		ShareMapper $shareMapper,
		Share $share,
		SystemController $systemController,
		MailService $mailService,
		Acl $acl
	) {
		$this->logger = $logger;
		$this->userId = $userId;
		$this->shareMapper = $shareMapper;
		$this->share = $share;
		$this->systemController = $systemController;
		$this->mailService = $mailService;
		$this->acl = $acl;
	}

	/**
	 * get
	 * Read all shares of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function list($pollId) {
		if ($this->acl->setPollId($pollId)->getAllowEdit()) {
			return $this->shareMapper->findByPoll($pollId);
		} else {
			throw new NotAuthorizedException;
		}
	}

	/**
	 * getByToken
	 * Get pollId by token
	 * @NoAdminRequired
	 * @param string $token
	 * @return Array
	 */
	public function get($token) {
		$this->share = $this->shareMapper->findByToken($token);
		return $this->share;
	}

	/**
	 * Write a new share to the db and returns the new share as array
	 * @NoAdminRequired
	 * @depricated
	 * @param int $pollId
	 * @param string $share
	 * @return Array
	 */
	 // TODO: Replace with $this->add and separate sending invitations
	public function write($pollId, $type, $userId, $userEmail = '') {
		$this->acl->setPollId($pollId);
		if (!$this->acl->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$this->share = new Share();
		$this->share->setType($type);
		$this->share->setPollId($pollId);
		$this->share->setUserId($userId);
		$this->share->setUserEmail($userEmail);
		$this->share->setToken(\OC::$server->getSecureRandom()->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		));

		$this->share = $this->shareMapper->insert($this->share);
		$sendResult = $this->mailService->sendInvitationMail($this->share->getToken());

		return [
			'share' => $this->share,
			'sendResult' => $sendResult
		];
	}

	/**
	 * Write a new share to the db and returns the new share as array
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $share
	 * @return Array
	 */
	public function add($pollId, $type, $userId, $userEmail = '') {
		$this->acl->setPollId($pollId);
		if (!$this->acl->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$this->share = new Share();
		$this->share->setType($type);
		$this->share->setPollId($pollId);
		$this->share->setUserId($userId);
		$this->share->setUserEmail($userEmail);
		$this->share->setToken(\OC::$server->getSecureRandom()->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		));

		return $this->shareMapper->insert($this->share);

	}

	/**
	 * createPersonalShare
	 * Write a new share to the db and returns the new share as array
	 * @NoAdminRequired
	 * @param string $token
	 * @param string $userName
	 * @return Share
	 */
	public function createPersonalShare($token, $userName) {

		$publicShare = $this->shareMapper->findByToken($token);

		// Return of validatePublicUsername is a DataResponse
		$checkUsername = $this->systemController->validatePublicUsername($publicShare->getPollId(), $userName, $token);

		// if status is not 200, return DataResponse from validatePublicUsername
		if ($checkUsername->getStatus() !== 200) {
			throw new InvalidUsername;
		}

		if ($publicShare->getType() === 'public') {

			$this->share = new Share();
			$this->share->setToken(\OC::$server->getSecureRandom()->generate(
				16,
				ISecureRandom::CHAR_DIGITS .
				ISecureRandom::CHAR_LOWER .
				ISecureRandom::CHAR_UPPER
			));
			$this->share->setType('external');
			$this->share->setPollId($publicShare->getPollId());
			$this->share->setUserId($userName);
			$this->share->setUserEmail('');
			$this->share = $this->shareMapper->insert($this->share);
			return $this->share;

		} elseif ($publicShare->getType() === 'email') {

			$publicShare->setType('external');
			$publicShare->setUserId($userName);
			$this->shareMapper->update($publicShare);
			return new DataResponse($publicShare, Http::STATUS_OK);

		} else {
			throw new NotAuthorizedException;
		}
	}

	/**
	 * remove
	 * remove share
	 * @NoAdminRequired
	 * @param string $token
	 * @return Share
	 */

	public function remove($token) {
		$this->share = $this->shareMapper->findByToken($token);
		if ($this->acl->setPollId($this->share->getPollId())->getAllowEdit()) {
			$this->shareMapper->delete($this->share);
			return $this->share;
		} else {
			throw new NotAuthorizedException;
		}
	}
}
