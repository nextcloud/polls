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

	/** @var PollService */
	private $pollService;

	/** @var CommentService */
	private $commentService;

	/** @var OptionService */
	private $optionService;

	/** @var ShareService */
	private $shareService;

	/** @var VoteService */
	private $voteService;

	/** @var Acl */
	private $acl;

 	/**
 	 * PollController constructor.
 	 * @param string $appName
 	 * @param IRequest $request
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
 		PollService $pollService,
		CommentService $commentService,
 		OptionService $optionService,
 		ShareService $shareService,
 		VoteService $voteService,
  		Acl $acl
	) {
 		parent::__construct($appName, $request);
		$this->pollService = $pollService;
		$this->commentService = $commentService;
  		$this->optionService = $optionService;
  		$this->shareService = $shareService;
  		$this->voteService = $voteService;
  		$this->acl = $acl;
	}


	/**
	 * Get list of polls
	 * @NoAdminRequired
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
	 * get complete poll
	 * @NoAdminRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param string $token
	 * @return DataResponse
	 */
 	public function get($pollId, $token) {
		try {
			if ($token) {
				$poll = $this->pollService->getByToken($token);
				$acl = $this->acl->setToken($token);
			} else {
				$poll = $this->pollService->get($pollId);
				$acl = $this->acl->setPollId($pollId);
			}

		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}

		try {
			$comments = $this->commentService->list($poll->getId(), $token);
		} catch (Exception $e) {
			$comments = [];
		}

		try {
			$options = $this->optionService->list($poll->getId(), $token);
		} catch (Exception $e) {
			$options = [];
		}

		try {
			$votes = $this->voteService->list($poll->getId(), $token);
		} catch (Exception $e) {
			$votes = [];
		}

		try {
			$shares = $this->shareService->list($poll->getId());
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
	 * Add poll
	 * @NoAdminRequired
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
	 * Update poll configuration
	 * @NoAdminRequired
	 * @param int $pollId
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
	 * Switch deleted status (move to deleted polls)
	 * @NoAdminRequired
	 * @param int $pollId
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
	 * Delete poll
	 * @NoAdminRequired
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
	 * Clone poll
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return DataResponse
	 */
	public function clone($pollId) {
		try {
			$poll = $this->pollService->clone($pollId);
			$this->optionService->clone($pollId, $poll->getId());

			return new DataResponse($poll, Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new DataResponse(['error' => 'Poll not found'], Http::STATUS_NOT_FOUND);
		} catch (NotAuthorizedException $e) {
			return new DataResponse(['error' => $e->getMessage()], $e->getStatus());
		}
	}

}
