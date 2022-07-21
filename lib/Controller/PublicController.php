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
	public function getPoll(string $token): JSONResponse {
		$this->acl->setToken($token);
		return $this->response(fn () => [
			'acl' => $this->acl,
			'poll' => $this->pollService->get($this->acl->getPollId()),
		], $token);
	}

	/**
	 * Watch poll for updates
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function watchPoll(string $token, ?int $offset): JSONResponse {
		$pollId = $this->acl->setToken($token)->getPollId();
		return $this->responseLong(fn () => [
			'updates' => $this->watchService->watchUpdates($pollId, $offset)
		], $token);
	}

	/**
	 * Get share
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function getShare(string $token): JSONResponse {
		$validateShareType = true;
		return $this->response(fn () => [
			'share' => $this->shareService->get($token, $validateShareType)
		], $token);
	}

	/**
	 * Get Comments
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function getComments(string $token): JSONResponse {
		return $this->response(fn () => [
			'comments' => $this->commentService->list(null, $token)
		], $token);
	}

	/**
	 * Get votes
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function getVotes(string $token): JSONResponse {
		return $this->response(fn () => [
			'votes' => $this->voteService->list(null, $token)
		], $token);
	}

	/**
	 * Delete user's votes
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function deleteUser(string $token): JSONResponse {
		return $this->response(fn () => [
			'deleted' => $this->voteService->delete(null, null, $token)
		], $token);
	}

	/**
	 * Get options
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function getOptions(string $token): JSONResponse {
		return $this->response(fn () => [
			'options' => $this->optionService->list(null, $token)
		], $token);
	}

	/**
	 * Add options
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function addOption(string $token, int $timestamp = 0, string $text = '', int $duration = 0): JSONResponse {
		return $this->responseCreate(fn () => [
			'option' => $this->optionService->add(null, $timestamp, $text, $duration, $token)
		], $token);
	}

	/**
	 * Delete option
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function deleteOption(string $token, int $optionId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => [
			'option' => $this->optionService->delete($optionId, $token)
		], $token);
	}

	/**
	 * Get subscription status
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function getSubscription(string $token): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->get(null, $token)
		], $token);
	}

	/**
	 * Set Vote
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function setVote(int $optionId, string $setTo, string $token): JSONResponse {
		return $this->response(fn () => [
			'vote' => $this->voteService->set($optionId, $setTo, $token)
		], $token);
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function addComment(string $token, string $message): JSONResponse {
		return $this->response(fn () => [
			'comment' => $this->commentService->add($message, null, $token)
		], $token);
	}

	/**
	 * Delete Comment
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function deleteComment(int $commentId, string $token): JSONResponse {
		return $this->responseDeleteTolerant(fn () => [
			'comment' => $this->commentService->delete($commentId, $token)
		], $token);
	}

	/**
	 * subscribe
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function subscribe(string $token): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(true, null, $token)
		], $token);
	}

	/**
	 * Unsubscribe
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function unsubscribe(string $token): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(false, null, $token)
		], $token);
	}

	/**
	 * Validate it the user name is reservrd
	 * return false, if this username already exists as a user or as
	 * a participant of the poll
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function validatePublicUsername(string $userName, string $token): JSONResponse {
		return $this->response(fn () => [
			'result' => $this->systemService->validatePublicUsername($userName, $token), 'name' => $userName
		], $token);
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
	public function setDisplayName(string $token, string $displayName): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setDisplayname($token, $displayName)
		], $token);
	}


	/**
	 * Set EmailAddress
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function setEmailAddress(string $token, string $emailAddress = ''): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setEmailAddress($token, $emailAddress, true)
		], $token);
	}

	/**
	 * Set EmailAddress
	 * @PublicPage
	 * @NoAdminRequired
	 */
	public function deleteEmailAddress(string $token): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->deleteEmailAddress($token)
		], $token);
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function register(string $token, string $userName, string $emailAddress = ''): JSONResponse {
		return $this->responseCreate(fn () => [
			'share' => $this->shareService->register($token, $userName, $emailAddress)
		], $token);
	}

	/**
	 * Sent invitation mails for a share
	 * Additionally send notification via notifications
	 * @NoAdminRequired
	 * @PublicPage
	 */
	public function resendInvitation(string $token): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->mailService->resendInvitation($token)
		], $token);
	}
}
