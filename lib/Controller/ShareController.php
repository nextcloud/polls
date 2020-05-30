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

namespace OCA\Polls\Controller;

use Exception;
use OCP\AppFramework\Db\DoesNotExistException;


use OCP\IRequest;
use OCP\ILogger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCP\Security\ISecureRandom;

use OCA\Polls\Db\Poll;

use OCA\Polls\Model\Acl;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Service\MailService;
// TODO: Change to Service
use OCA\Polls\Controller\SystemController;

class ShareController extends Controller {

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
	 * @param PollMapper $pollMapper
	 * @param SystemController $systemController
	 * @param MailService $mailService
	 * @param Acl $acl
	 */
	public function __construct(
		string $appName,
		$userId,
		IRequest $request,
		ILogger $logger,
		ShareMapper $shareMapper,
		Share $share,
		PollMapper $pollMapper,
		SystemController $systemController,
		MailService $mailService,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->userId = $userId;
		$this->shareMapper = $shareMapper;
		$this->share = $share;
		$this->pollMapper = $pollMapper;
		$this->systemController = $systemController;
		$this->mailService = $mailService;
		$this->acl = $acl;
	}

	/**
	 * get
	 * Get share by token
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param string $token
	 * @return DataResponse Share
	 */
	public function get($token) {
		try {
			$this->share = $this->shareMapper->findByToken($token);
			return new DataResponse($this->share, Http::STATUS_OK);

		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * list
	 * Generates array of shares based on $pollId
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return DataResponse Array of Share
	 */
	public function list($pollId) {
		if ($this->acl->setPollId($pollId)->getAllowEdit()) {
			try {
				$shares = $this->shareMapper->findByPoll($pollId);
				return new DataResponse((array) $shares, Http::STATUS_OK);

			} catch (DoesNotExistException $e) {
				return new DataResponse($e, Http::STATUS_NOT_FOUND);
			}

		} else {
			$this->logger->alert('no access');

			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

	}

	/**
	 * add
	 * Add a share
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @param Array $share
	 * @return DataResponse Array of Share
	 */
	public function add($pollId, $share) {
		$this->acl->setPollId($pollId);
		if (!$this->acl->getAllowEdit()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		$this->share = new Share();
		$this->share->setType($share['type']);
		$this->share->setPollId($share['pollId']);
		$this->share->setUserId($share['userId']);
		$this->share->setUserEmail(isset($share['userEmail']) ? $share['userEmail'] : '');
		$this->share->setToken(\OC::$server->getSecureRandom()->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		));

		try {
			$this->share = $this->shareMapper->insert($this->share);
			$sendResult = $this->mailService->sendInvitationMail($this->share->getToken());

			return new DataResponse([
				'sendResult' => $sendResult,
				'shares' => $this->shareMapper->findByPoll($pollId),
			], Http::STATUS_OK);

		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
		}

	}

	/**
	 * createPersonalShare
	 * Create a new personal share from public share
	 * or update email share
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $token
	 * @param string $userName
	 * @param string $userEmail
	 * @return DataResponse Share
	 */
	public function createPersonalShare($token, $userName, $userEmail = '') {

		try {
			$this->share = $this->shareMapper->findByToken($token);

			// Return of validatePublicUsername is a DataResponse
			$checkUsername = $this->systemController->validatePublicUsername($this->share->getPollId(), $userName, $token);

			// if status is not 200, return DataResponse from validatePublicUsername
			if ($checkUsername->getStatus() !== 200) {
				return $checkUsername;
			}

			if ($this->share->getType() === 'email') {

				$this->share->setType('external');
				$this->share->setUserId($userName);
				$this->shareMapper->update($this->share);

			} elseif ($this->share->getType() === 'public') {

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
				$this->share->setUserEmail($userEmail);
				$this->share = $this->shareMapper->insert($this->share);

			} else {
				return new DataResponse([
					'message'=> 'Wrong share type: ' . $this->share->getType()
				], Http::STATUS_FORBIDDEN);
			}

			return new DataResponse($this->share, Http::STATUS_OK);

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * remove
	 * remove share
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Array $share
	 * @return DataResponse
	 */

	public function remove($share) {
		try {
			if ($this->acl->setPollId($share['pollId'])->getAllowEdit()) {
				$this->shareMapper->remove($share['id']);

				return new DataResponse(array(
					'shares' => $this->shareMapper->findByPoll($share['pollId']),
				), Http::STATUS_OK);
			} else {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}
}
