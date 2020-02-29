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
	private $mapper;
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
	 * @param ShareMapper $mapper
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
		ShareMapper $mapper,
		PollMapper $pollMapper,
		SystemController $systemController,
		MailService $mailService,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->userId = $userId;
		$this->mapper = $mapper;
		$this->pollMapper = $pollMapper;
		$this->systemController = $systemController;
		$this->mailService = $mailService;
		$this->acl = $acl;
	}

	/**
	 * getByToken
	 * Get pollId by token
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param string $token
	 * @return DataResponse
	 */
	public function get($token) {
		try {
			$share = $this->mapper->findByToken($token);
			return new DataResponse($share, Http::STATUS_OK);

		} catch (DoesNotExistException $e) {
			return new DataResponse(null, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * get
	 * Read all shares of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function getShares($pollId) {
		if ($this->acl->setPollId($pollId)->getAllowEdit()) {
			try {
				$shares = $this->mapper->findByPoll($pollId);
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
	 * write
	 * Write a new share to the db and returns the new share as array
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $message
	 * @return DataResponse
	 */
	public function write($pollId, $share) {
		$this->acl->setPollId($pollId);
		if (!$this->acl->getAllowEdit()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		$newShare = new Share();
		$newShare->setType($share['type']);
		$newShare->setPollId($share['pollId']);
		$newShare->setUserId($share['userId']);
		$newShare->setUserEmail(isset($share['userEmail']) ? $share['userEmail'] : '');
		$newShare->setToken(\OC::$server->getSecureRandom()->generate(
			16,
			ISecureRandom::CHAR_DIGITS .
			ISecureRandom::CHAR_LOWER .
			ISecureRandom::CHAR_UPPER
		));

		try {
			$newShare = $this->mapper->insert($newShare);
			// $this->logger->debug('Share inserted, sending out invitation mail now.');
			$sendResult = $this->mailService->sendInvitationMail($newShare->getToken());
			// $this->logger->debug('Sending result ' . json_encode($sendResult));

			return new DataResponse([
				'share' => $newShare,
				'sendResult' => $sendResult
			], Http::STATUS_OK);

		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
		}

	}

	/**
	 * createPersonalShare
	 * Write a new share to the db and returns the new share as array
	 * @NoAdminRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param string $message
	 * @return DataResponse
	 */
	public function createPersonalShare($token, $userName) {

		try {
			$publicShare = $this->mapper->findByToken($token);
			if (!$this->systemController->validatePublicUsername($publicShare->getPollId(), $userName)) {
				return new DataResponse(['message' => 'invalid userName'], Http::STATUS_CONFLICT);
			}

			if ($publicShare->getType() === 'public') {

				$userShare = new Share();
				$userShare->setToken(\OC::$server->getSecureRandom()->generate(
					16,
					ISecureRandom::CHAR_DIGITS .
					ISecureRandom::CHAR_LOWER .
					ISecureRandom::CHAR_UPPER
				));
				$userShare->setType('external');
				$userShare->setPollId($publicShare->getPollId());
				$userShare->setUserId($userName);
				$userShare->setUserEmail('');
				$this->logger->debug('Create share: ' . json_encode($userShare));
				$userShare = $this->mapper->insert($userShare);
				return new DataResponse($userShare, Http::STATUS_OK);

			} else {
				return new DataResponse(['message'=> 'Wrong share type: ' . $userShare->getType()], Http::STATUS_FORBIDDEN);
			}

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * remove
	 * remove share
	 * @NoAdminRequired
	 * @param Share $share
	 * @return DataResponse
	 */

	public function remove($share) {
		try {
			if ($this->acl->setPollId($share['pollId'])->getAllowEdit()) {
				$this->mapper->remove($share['id']);

				return new DataResponse(array(
					'action' => 'deleted',
					'shareId' => $share['id']
				), Http::STATUS_OK);
			} else {
				return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
			}

		} catch (Exception $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}
	}
}
