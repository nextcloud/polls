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

use OCP\IGroupManager;

use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Comment;
use OCA\Polls\Db\CommentMapper;
use OCA\Polls\Service\AnonymizeService;
use OCA\Polls\Model\Acl;



class CommentController extends Controller {

	private $userId;
	private $mapper;
	private $logger;

	private $groupManager;
	private $pollMapper;
	private $anonymizer;
	private $acl;

	/**
	 * CommentController constructor.
	 * @param string $appName
	 * @param $UserId
	 * @param CommentMapper $mapper
	 * @param IGroupManager $groupManager
	 * @param PollMapper $pollMapper
	 * @param AnonymizeService $anonymizer
	 * @param Acl $acl
	 */

	public function __construct(
		string $appName,
		$userId,
		IRequest $request,
		ILogger $logger,
		CommentMapper $mapper,
		IGroupManager $groupManager,
		PollMapper $pollMapper,
		AnonymizeService $anonymizer,
		Acl $acl
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->mapper = $mapper;
		$this->logger = $logger;
		$this->groupManager = $groupManager;
		$this->pollMapper = $pollMapper;
		$this->anonymizer = $anonymizer;
		$this->acl = $acl;
	}


	/**
	 * get
	 * Read all comments of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function get($pollId) {

		try {
			if (!$this->acl->getFoundByToken()) {
				$this->acl->setPollId($pollId);
			}

			if (!$this->acl->getAllowSeeUsernames()) {
				$this->anonymizer->set($pollId, $this->acl->getUserId());
				return new DataResponse((array) $this->anonymizer->getComments(), Http::STATUS_OK);
			} else {
				return new DataResponse((array) $this->mapper->findByPoll($pollId), Http::STATUS_OK);
			}

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

	}

	/**
	 * getByToken
	 * Read all comments of a poll based on a share token and return list as array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param string $token
	 * @return DataResponse
	 */
	public function getByToken($token) {

		try {
			$this->acl->setToken($token);
		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}

		return $this->get($this->acl->getPollId());

	}

	/**
	 * write
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param int $pollId
	 * @param string $userId
	 * @param string $message
	 * @return DataResponse
	 */
	public function write($pollId, $userId, $message) {
		$this->logger->alert('write');
		if (!\OC::$server->getUserSession()->isLoggedIn() && !$this->acl->getFoundByToken()) {
			$this->logger->alert('not allowed ' . json_encode(\OC::$server->getUserSession()->isLoggedIn()));
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		if (!$this->acl->getFoundByToken()) {
			$this->acl->setPollId($pollId);
		}

		if ($this->acl->getAllowComment()) {
			$comment = new Comment();
			$comment->setPollId($pollId);
			$comment->setUserId($userId);
			$comment->setComment($message);
			$comment->setDt(date('Y-m-d H:i:s'));


			try {
				$comment = $this->mapper->insert($comment);
			} catch (\Exception $e) {
				$this->logger->alert('conflict ' . json_encode($e));
				return new DataResponse($e, Http::STATUS_CONFLICT);
			}
		} else {
			$this->logger->alert('unauthorized ');
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}


		$this->logger->alert('ok ' . json_encode($comment));
		return new DataResponse($comment, Http::STATUS_OK);

	}

	/**
	 * writeByToken
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param Array $option
	 * @param string $setTo
	 * @param string $token
	 * @return DataResponse
	 */
	public function writeByToken($token, $message) {

		try {
			$this->acl->setToken($token);
			return $this->write($this->acl->getPollId(), $this->acl->getUserId(), $message);

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}


	}


	/**
	 * delete
	 * Delete Comment
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $message
	 * @return DataResponse
	 */
	public function delete($comment) {
		if (!\OC::$server->getUserSession()->isLoggedIn() && !$this->acl->getFoundByToken()) {
			return new DataResponse(null, Http::STATUS_UNAUTHORIZED);
		}

		if (!$this->acl->getFoundByToken()) {
			$this->acl->setPollId($comment['pollId']);
		}

		try {
			if ($comment['userId'] === $this->acl->getUserId()) {
					$comment = $this->mapper->find($comment['id']);
					$comment = $this->mapper->delete($comment);
			}
		} catch (\Exception $e) {
			return new DataResponse($e, Http::STATUS_CONFLICT);
		}

		return new DataResponse(['comment' => $comment], Http::STATUS_OK);

	}

	/**
	 * writeByToken
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param Array $option
	 * @param string $setTo
	 * @param string $token
	 * @return DataResponse
	 */
	public function deleteByToken($token, $comment) {

		try {
			$this->acl->setToken($token);
			return $this->delete($comment);

		} catch (DoesNotExistException $e) {
			return new DataResponse($e, Http::STATUS_NOT_FOUND);
		}



	}

}
