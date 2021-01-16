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



use OCA\Polls\Db\Share;
use OCA\Polls\Db\Poll;
use OCA\Polls\Model\Acl;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\MailService;
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

	/** @var MailService */
	private $mailService;

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
		MailService $mailService,
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
		$this->mailService = $mailService;
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
	 * @return TemplateResponse|PublicTemplateResponse
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
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function getPoll(string $token): DataResponse {
		return $this->response(function () use ($token) {
			$this->share = $this->shareService->get($token, true);
			$this->acl->setShare($this->share);
			$this->poll = $this->pollService->get($this->share->getPollId());
			return [
				'acl' => $this->acl,
				'poll' => $this->poll,
			];
		});
	}

	/**
	 * Get share
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function getShare(string $token): DataResponse {
		return $this->response(function () use ($token) {
			return ['share' => $this->shareService->get($token, true)];
		});
	}

	/**
	 * Get Comments
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function getComments(string $token): DataResponse {
		return $this->response(function () use ($token) {
			return ['comments' => $this->commentService->list(null, $token)];
		});
	}

	/**
	 * Get votes
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function getVotes(string $token): DataResponse {
		return $this->response(function () use ($token) {
			return ['votes' => $this->voteService->list(null, $token)];
		});
	}

	/**
	 * Get options
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function getOptions(string $token): DataResponse {
		return $this->response(function () use ($token) {
			return ['options' => $this->optionService->list(null, $token)];
		});
	}

	/**
	 * Get subscription status
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function getSubscription(string $token): DataResponse {
		return $this->response(function () use ($token) {
			return ['subscribed' => $this->subscriptionService->get(0, $token)];
		});
	}

	/**
	 * Set Vote
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function setVote(int $optionId, string $setTo, string $token): DataResponse {
		return $this->response(function () use ($optionId, $setTo, $token) {
			return ['vote' => $this->voteService->set($optionId, $setTo, $token)];
		});
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function addComment(string $token, string $message): DataResponse {
		return $this->response(function () use ($token, $message) {
			return ['comment' => $this->commentService->add(null, $token, $message)];
		});
	}

	/**
	 * Delete Comment
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function deleteComment(int $commentId, string $token): DataResponse {
		return $this->responseDeleteTolerant(function () use ($commentId, $token) {
			return ['comment' => $this->commentService->delete($commentId, $token)];
		});
	}

	/**
	 * subscribe
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function subscribe(string $token): DataResponse {
		return $this->response(function () use ($token) {
			return ['subscribed' => $this->subscriptionService->set(0, $token, true)];
		});
	}

	/**
	 * Unsubscribe
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function unsubscribe(string $token): DataResponse {
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
	public function validatePublicUsername(string $userName, string $token): DataResponse {
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
	public function validateEmailAddress(string $emailAddress): DataResponse {
		try {
			return new DataResponse(['result' => $this->systemService->validateEmailAddress($emailAddress), 'emailAddress' => $emailAddress], Http::STATUS_OK);
		} catch (\Exception $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_CONFLICT);
		}
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function register(string $token, string $userName, string $emailAddress = ''): DataResponse {
		return $this->responseCreate(function () use ($token, $userName, $emailAddress) {
			return ['share' => $this->shareService->personal($token, $userName, $emailAddress)];
		});
	}

	/**
	 * Sent invitation mails for a share
	 * Additionally send notification via notifications
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function resendInvitation(string $token): DataResponse {
		return $this->response(function () use ($token) {
			return ['share' => $this->mailService->resendInvitation($token)];
		});
	}
}
