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

use OCP\Security\ISecureRandom;

use OCA\Polls\Exceptions\NotAuthorizedException;
use OCA\Polls\Exceptions\InvalidUsername;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Service\MailService;
use OCA\Polls\Model\Acl;
use OCA\Polls\Controller\SystemController;

class ShareService {

	private $shareMapper;
	private $share;
	private $systemController;
	private $mailService;
	private $acl;

	/**
	 * ShareController constructor.
	 * @param ShareMapper $shareMapper
	 * @param Share $share
	 * @param SystemController $systemController
	 * @param MailService $mailService
	 * @param Acl $acl
	 */
	public function __construct(
		ShareMapper $shareMapper,
		Share $share,
		SystemController $systemController,
		MailService $mailService,
		Acl $acl
	) {
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
	 * @return array
	 */
	public function list($pollId) {
		if (!$this->acl->setPollId($pollId)->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		return $this->shareMapper->findByPoll($pollId);
	}

	/**
	 * getByToken
	 * Get pollId by token
	 * @NoAdminRequired
	 * @param string $token
	 * @return Share
	 */
	public function get($token) {
		return $this->shareMapper->findByToken($token);
	}

	/**
	 * Write a new share to the db and returns the new share as array
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $share
	 * @return array
	 */
	public function add($pollId, $type, $userId, $userEmail = '') {

		if (!$this->acl->setPollId($pollId)->getAllowEdit()) {
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
			$this->share->setInvitationSent(time());
			return $this->shareMapper->insert($this->share);

		} elseif ($publicShare->getType() === 'email') {

			$publicShare->setType('external');
			$publicShare->setUserId($userName);
			return $this->shareMapper->update($publicShare);

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
		if (!$this->acl->setPollId($this->share->getPollId())->getAllowEdit()) {
			throw new NotAuthorizedException;
		}

		$this->shareMapper->delete($this->share);

		return $this->share;
	}
}
