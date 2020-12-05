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


use OCA\Polls\Db\Share;
use OCA\Polls\Db\Poll;
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
	 */
	public function votePage(): PublicTemplateResponse {
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
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function poll(string $token): DataResponse {
		return $this->response(function () use ($token) {
			$this->share = $this->shareService->get($token);
			$this->acl->setShare($this->share);
			$this->poll = $this->pollService->get($this->share->getPollId());
			return $this->buildPoll();
		});
	}

	/**
	 * Set vote with token
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function vote($optionId, $setTo, $token): DataResponse {
		return $this->response(function () use ($optionId, $setTo, $token) {
			return ['vote' =>$this->voteService->set($optionId, $setTo, $token)];
		});
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function comment($token, $message): DataResponse {
		return $this->response(function () use ($token, $message) {
			return ['comment'=> $this->commentService->add(null, $token, $message)];
		});
	}

	/**
	 * Delete Comment
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function commentDelete($commentId, $token): DataResponse {
		return $this->responseDeleteTolerant(function () use ($commentId, $token) {
			return ['comment'=> $this->commentService->delete($commentId, $token)];
		});
	}
	/**
	 * Get subscription status
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function subscription($token): DataResponse {
		return $this->response(function () use ($token) {
			return ['subscribed' => $this->subscriptionService->get(0, $token)];
		});
	}

	/**
	 * subscribe
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function subscribe($token): DataResponse {
		return $this->response(function () use ($token) {
			return ['subscribed' => $this->subscriptionService->set(0, $token, true)];
		});
	}

	/**
	 * Unsubscribe
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function unsubscribe($token): DataResponse {
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
	 */
	public function validatePublicUsername($userName, $token): DataResponse {
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
	 */
	public function validateEmailAddress($emailAddress): DataResponse {
		try {
			return new DataResponse(['result' => $this->systemService->validateEmailAddress($emailAddress), 'emailAddress' => $emailAddress], Http::STATUS_OK);
		} catch (\Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_CONFLICT);
		}
	}

	/**
	 * get complete poll
	 * @NoAdminRequired
	 */
	private function buildPoll(): Array {
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
