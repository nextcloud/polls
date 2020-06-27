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
use OCA\Polls\Exceptions\EmptyTitleException;
use OCA\Polls\Exceptions\InvalidAccessException;
use OCA\Polls\Exceptions\InvalidShowResultsException;
use OCA\Polls\Exceptions\InvalidPollTypeException;
use OCA\Polls\Exceptions\NotAuthorizedException;

use OCP\IRequest;
use OCP\ILogger;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCA\Polls\Service\PollService;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\VoteService;
use OCA\Polls\Model\Acl;

class PollController extends Controller {

	private $logger;
	private $pollService;
	private $commentService;
	private $optionService;
	private $shareService;
	private $voteService;
	private $acl;

 	/**
 	 * PollController constructor.
 	 * @param string $appName
 	 * @param IRequest $request
 	 * @param ILogger $logger
 	 * @param PollService $pollService
	 * @param CommentService $commentService
  	 * @param OptionService $optionService
  	 * @param ShareService $shareService
  	 * @param VoteService $voteService
  	 * @param Acl $acl
	 */

 	public function __construct(
		string $appName,
 		IRequest $request,
 		ILogger $logger,
 		PollService $pollService,
		CommentService $commentService,
 		OptionService $optionService,
 		ShareService $shareService,
 		VoteService $voteService,
  		Acl $acl
	) {
 		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->pollService = $pollService;
		$this->commentService = $commentService;
  		$this->optionService = $optionService;
  		$this->shareService = $shareService;
  		$this->voteService = $voteService;
  		$this->acl = $acl;
	}


	/**
	 * list
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return DataResponse
	 */

	public function list() {
		try {
			return new DataResponse($this->pollService->list(), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}


	/**
	 * get
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 * @param integer $pollId
	 * @return array
	 */
 	public function get($pollId, $token) {
		try {
			if ($token) {
				$poll = $this->pollService->get(0, $token);
				$acl = $this->acl->setToken($token);
			} else {
				$poll = $this->pollService->get($pollId);
				$acl = $this->acl->setPollId($pollId);
			}

			// $this->poll = $this->pollService->get($pollId, $token);
			// return new DataResponse($this->pollService->get($pollId, $token), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}

		try {
			$comments = $this->commentService->list($pollId, $token);
		} catch (Exception $e) {
			$comments = [];
		}

		try {
			$options = $this->optionService->list($pollId, $token);
		} catch (Exception $e) {
			$options = [];
		}

		try {
			$votes = $this->voteService->list($pollId, $token);
		} catch (Exception $e) {
			$votes = [];
		}

		try {
			$shares = $this->shareService->list($pollId);
		} catch (Exception $e) {
			$shares = [];
		}

		return new DataResponse([
			'acl' => $acl,
			'poll' => $poll,
			'comments' => $comments,
			'options' => $options,
			'shares' => $shares,
			'votes' => $votes
		], Http::STATUS_OK);
 	}

	/**
	 * delete
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Array $poll
	 * @return DataResponse
	 */

	public function delete($pollId) {
		try {
			return new DataResponse($this->pollService->delete($pollId), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * deletePermanently
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param Array $poll
	 * @return DataResponse
	 */

	public function deletePermanently($pollId) {
		try {
			return new DataResponse($this->pollService->deletePermanently($pollId), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}


	/**
	 * add
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $type
	 * @param string $title
	 * @return DataResponse
	 */

	public function add($type, $title) {
		try {
			return new DataResponse($this->pollService->add($type, $title), Http::STATUS_OK);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (InvalidPollTypeException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (EmptyTitleException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * write
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @param array $poll
	 * @return DataResponse
	 */

	public function update($pollId, $poll) {
		try {
			return new DataResponse($this->pollService->update($pollId, $poll), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (InvalidAccessException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (InvalidShowResultsException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		} catch (EmptyTitleException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * clone
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param integer $pollId
	 * @return DataResponse
	 */
	public function clone($pollId) {
		try {
			return new DataResponse($this->pollService->clone($pollId), Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

}
