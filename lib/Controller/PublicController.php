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

use OCA\Polls\Model\Acl;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\MailService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SubscriptionService;
use OCA\Polls\Service\VoteService;
use OCA\Polls\Service\SystemService;
use OCA\Polls\Service\WatchService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\ISession;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class PublicController extends BaseController {
	/** @var Acl */
	private $acl;
	
	/** @var CommentService */
	private $commentService;

	/** @var MailService */
	private $mailService;

	/** @var OptionService */
	private $optionService;
	
	/** @var PollService */
	private $pollService;
	
	/** @var ShareService */
	private $shareService;
	
	/** @var SubscriptionService */
	private $subscriptionService;
	
	/** @var SystemService */
	private $systemService;

	/** @var string */
	private $token;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IUserSession */
	private $userSession;
	
	/** @var VoteService */
	private $voteService;

	/** @var WatchService */
	private $watchService;

	public function __construct(
		string $appName,
		IRequest $request,
		ISession $session,
		IURLGenerator $urlGenerator,
		IUserSession $userSession,
		Acl $acl,
		CommentService $commentService,
		MailService $mailService,
		OptionService $optionService,
		PollService $pollService,
		ShareService $shareService,
		SubscriptionService $subscriptionService,
		SystemService $systemService,
		VoteService $voteService,
		WatchService $watchService
	) {
		parent::__construct($appName, $request, $session);
		$this->urlGenerator = $urlGenerator;
		$this->userSession = $userSession;
		$this->acl = $acl;
		$this->commentService = $commentService;
		$this->mailService = $mailService;
		$this->optionService = $optionService;
		$this->pollService = $pollService;
		$this->shareService = $shareService;
		$this->subscriptionService = $subscriptionService;
		$this->systemService = $systemService;
		$this->voteService = $voteService;
		$this->watchService = $watchService;
		$this->token = $this->session->get('publicPollToken');
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return TemplateResponse|PublicTemplateResponse
	 */
	public function votePage() {
		if ($this->userSession->isLoggedIn()) {
			return new TemplateResponse('polls', 'polls.tmpl', ['urlGenerator' => $this->urlGenerator]);
		} else {
			return new PublicTemplateResponse('polls', 'polls.tmpl', ['urlGenerator' => $this->urlGenerator]);
		}
	}

	/**
	 * get complete poll via token
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function getPoll(): JSONResponse {
		return $this->response(fn () => [
			'acl' => $this->acl,
			'poll' => $this->pollService->get($this->acl->getPollId()),
		]);
	}

	/**
	 * Watch poll for updates
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function watchPoll(?int $offset): JSONResponse {
		return $this->responseLong(fn () => [
			'updates' => $this->watchService->watchUpdates($this->acl->getPollId(), $offset)
		]);
	}

	/**
	 * Get share
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function getShare(): JSONResponse {
		$validateShareType = true;
		return $this->response(fn () => [
			'share' => $this->shareService->get($this->token, $validateShareType)
		]);
	}

	/**
	 * Get votes
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function getVotes(): JSONResponse {
		return $this->response(fn () => [
			'votes' => $this->voteService->list(null, $this->acl)
		]);
	}

	/**
	 * Delete user's votes
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function deleteUser(): JSONResponse {
		return $this->response(fn () => [
			'deleted' => $this->voteService->delete(null, null, $this->acl->request(Acl::PERMISSION_VOTE_EDIT))
		]);
	}

	/**
	 * Get options
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function getOptions(): JSONResponse {
		return $this->response(fn () => [
			'options' => $this->optionService->list(null, $this->acl)
		]);
	}

	/**
	 * Add options
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function addOption(int $timestamp = 0, string $text = '', int $duration = 0): JSONResponse {
		return $this->responseCreate(fn () => [
			'option' => $this->optionService->add(
				null,
				$timestamp,
				$text,
				$duration,
				$this->acl->request(Acl::PERMISSION_OPTIONS_ADD)
			)
		]);
	}

	/**
	 * Delete option
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function deleteOption(int $optionId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => [
			'option' => $this->optionService->delete($optionId, $this->acl->request(Acl::PERMISSION_POLL_VIEW))
		]);
	}

	/**
	 * Set Vote
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function setVote(int $optionId, string $setTo): JSONResponse {
		return $this->response(fn () => [
			'vote' => $this->voteService->set($optionId, $setTo, $this->acl->request(Acl::PERMISSION_COMMENT_ADD))
		]);
	}

	/**
	 * Get Comments
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function getComments(): JSONResponse {
		return $this->response(fn () => [
			'comments' => $this->commentService->list($this->acl)
		]);
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function addComment(string $message): JSONResponse {
		return $this->response(fn () => [
			'comment' => $this->commentService->add($message, $this->acl->request(Acl::PERMISSION_COMMENT_ADD))
		]);
	}

	/**
	 * Delete Comment
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function deleteComment(int $commentId): JSONResponse {
		$comment = $this->commentService->get($commentId);
		return $this->responseDeleteTolerant(fn () => [
			'comment' => $this->commentService->delete($comment, $this->acl->request(Acl::PERMISSION_COMMENT_ADD))
		]);
	}

	/**
	 * Get subscription status
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function getSubscription(): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->get($this->acl)
		]);
	}

	/**
	 * subscribe
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function subscribe(): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(true, $this->acl)
		]);
	}

	/**
	 * Unsubscribe
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function unsubscribe(): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(false, $this->acl)
		]);
	}

	/**
	 * Validate it the user name is reservrd
	 * return false, if this username already exists as a user or as
	 * a participant of the poll
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function validatePublicUsername(string $userName): JSONResponse {
		return $this->response(fn () => [
			'result' => $this->systemService->validatePublicUsername($userName, $this->shareService->get($this->token)), 'name' => $userName
		]);
	}

	/**
	 * Validate email address (simple validation)
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function validateEmailAddress(string $emailAddress): JSONResponse {
		return $this->response(fn () => [
			'result' => $this->systemService->validateEmailAddress($emailAddress), 'emailAddress' => $emailAddress
		]);
	}

	/**
	 * Change displayName
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function setDisplayName(string $displayName): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setDisplayname($this->shareService->get($this->token), $displayName)
		]);
	}


	/**
	 * Set EmailAddress
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function setEmailAddress(string $emailAddress = ''): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setEmailAddress($this->shareService->get($this->token), $emailAddress, true)
		]);
	}

	/**
	 * Set EmailAddress
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function deleteEmailAddress(): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->deleteEmailAddress($this->shareService->get($this->token))
		]);
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function register(string $userName, string $emailAddress = '', string $timeZone = ''): JSONResponse {
		return $this->responseCreate(fn () => [
			'share' => $this->shareService->register($this->shareService->get($this->token), $userName, $emailAddress, $timeZone)
		]);
	}

	/**
	 * Sent invitation mails for a share
	 * Additionally send notification via notifications
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function resendInvitation(): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->mailService->resendInvitation($this->token)
		]);
	}
}
