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

use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;

use OCA\Polls\Exceptions\Exception;


use OCA\Polls\DB\Share;
use OCA\Polls\DB\Poll;
use OCA\Polls\Model\Acl;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SubscriptionService;
use OCA\Polls\Service\VoteService;
use OCA\Polls\Service\SystemService;

class PublicController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var Acl */
	private $acl;

	/** @var CommentService */
	private $commentService;

	/** @var OptionService */
	private $optionService;

	/** @var PollService */
	private $pollService;

	/** @var Poll */
	private $poll;

	/** @var ShareService */
	private $shareService;

	/** @var SubscriptionService */
	private $subscriptionService;

	/** @var Share */
	private $share;

	/** @var VoteService */
	private $voteService;

	/** @var SystemService */
	private $systemService;

	use ResponseHandle;

	/**
	 * PollController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param Acl $acl
	 * @param CommentService $commentService
	 * @param OptionService $optionService
	 * @param PollService $pollService
	 * @param Poll $poll
	 * @param ShareService $shareService
	 * @param Share $share
	 * @param SubscriptionService $subscriptionService
	 * @param VoteService $voteService
	 * @param SystemService $systemService
	 */

	public function __construct(
		string $appName,
		IRequest $request,
		IURLGenerator $urlGenerator,
		Acl $acl,
		CommentService $commentService,
		OptionService $optionService,
		PollService $pollService,
		Poll $poll,
		ShareService $shareService,
		Share $share,
		SubscriptionService $subscriptionService,
		VoteService $voteService,
		SystemService $systemService
	) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->acl = $acl;
		$this->commentService = $commentService;
		$this->optionService = $optionService;
		$this->pollService = $pollService;
		$this->poll = $poll;
		$this->shareService = $shareService;
		$this->share = $share;
		$this->subscriptionService = $subscriptionService;
		$this->voteService = $voteService;
		$this->systemService = $systemService;
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param string $token
	 * @return PublicTemplateResponse
	 */
	public function votePage() {
		if (\OC::$server->getUserSession()->isLoggedIn()) {
			return new TemplateResponse('polls', 'polls.tmpl', [
				'urlGenerator' => $this->urlGenerator]);
		} else {
			return new PublicTemplateResponse('polls', 'polls.tmpl', [
				'urlGenerator' => $this->urlGenerator]);
		}
	}

	/**
	 * get complete poll via token
	 * @NoAdminRequired
	 * @PublicPage
	 * @param string $token
	 * @return DataResponse
	 */
	public function poll(string $token) {
		return $this->response(function () use ($token) {
			$this->share = $this->shareService->get($token);
			$this->acl->setShare($this->share);
			$this->poll = $this->pollService->get($this->share->getPollId());
			return $this->buildPoll();
		});
	}

	/**
	 * Set vote with token
	 * @NoAdminRequired
	 * @PublicPage
	 * @param Array $option
	 * @param string $setTo
	 * @param string $token
	 * @return DataResponse
	 */
	public function vote($optionId, $setTo, $token) {
		return $this->response(function () use ($optionId, $setTo, $token) {
			return ['vote' =>$this->voteService->set($optionId, $setTo, $token)];
		});
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @PublicPage
	 * @param int $pollId
	 * @param string $message
	 * @param string $token
	 * @return DataResponse
	 */
	public function comment($token, $message) {
		return $this->response(function () use ($token, $message) {
			return ['comment'=> $this->commentService->add(null, $token, $message)];
		});
	}

	/**
	 * Delete Comment
	 * @NoAdminRequired
	 * @PublicPage
	 * @param int $commentId
	 * @param string $token
	 * @return DataResponse
	 */
	public function commentDelete($commentId, $token) {
		return $this->responseDeleteTolerant(function () use ($commentId, $token) {
			return ['comment'=> $this->commentService->delete($commentId, $token)];
		});
	}
	/**
	 * Get subscription status
	 * @PublicPage
	 * @NoAdminRequired
	 * @param int $pollId
	 * @return DataResponse
	 * @throws DoesNotExistException
	 */
	public function subscription($token) {
		return $this->response(function () use ($token) {
			return ['subscribed' => $this->subscriptionService->get(0, $token)];
		});
	}

	/**
	 * subscribe
	 * @PublicPage
	 * @NoAdminRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function subscribe($token) {
		return $this->response(function () use ($token) {
			return ['subscribed' => $this->subscriptionService->set(0, $token, true)];
		});
	}

	/**
	 * Unsubscribe
	 * @PublicPage
	 * @NoAdminRequired
	 * @param string $token
	 * @return DataResponse
	 */
	public function unsubscribe($token) {
		return $this->response(function () use ($token) {
			return ['subscribed' => $this->subscriptionService->set(0, $token, false)];
		});
	}

	/**
	 * Validate it the user name is reservrd
	 * return false, if this username already exists as a user or as
	 * a participant of the poll
	 * @NoAdminRequired
	 * @PublicPage
	 * @return DataResponse
	 */
	public function validatePublicUsername($userName, $token) {
		try {
			return new DataResponse(['result' => $this->systemService->validatePublicUsername($userName, $token), 'name' => $userName], Http::STATUS_OK);
		} catch (\Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_CONFLICT);
		}
	}

	/**
	 * Validate email address (simple validation)
	 * @NoAdminRequired
	 * @PublicPage
	 * @return DataResponse
	 */
	public function validateEmailAddress($emailAddress) {
		try {
			return new DataResponse(['result' => $this->systemService->validateEmailAddress($emailAddress), 'emailAddress' => $emailAddress], Http::STATUS_OK);
		} catch (\Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_CONFLICT);
		}
	}

	/**
	 * get complete poll
	 * @NoAdminRequired
	 * @param int $pollId
	 * @param string $token
	 * @return Array
	 */
	private function buildPoll() {
		try {
			$comments = $this->commentService->list($this->poll->getId());
		} catch (Exception $e) {
			$comments = [];
		}

		try {
			$options = $this->optionService->list($this->poll->getId());
		} catch (Exception $e) {
			$options = [];
		}

		try {
			$votes = $this->voteService->list($this->poll->getId());
		} catch (Exception $e) {
			$votes = [];
		}

		return [
			'acl' => $this->acl,
			'poll' => $this->poll,
			'comments' => $comments,
			'options' => $options,
			'share' => $this->share,
			'shares' => [],
			'votes' => $votes,
		];
	}
}
