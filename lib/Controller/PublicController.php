<?php

declare(strict_types=1);
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

use OCA\Polls\AppConstants;
use OCA\Polls\Model\Acl;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\MailService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SubscriptionService;
use OCA\Polls\Service\SystemService;
use OCA\Polls\Service\VoteService;
use OCA\Polls\Service\WatchService;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Util;

class PublicController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		ISession $session,
		private IURLGenerator $urlGenerator,
		private IUserSession $userSession,
		private Acl $acl,
		private CommentService $commentService,
		private MailService $mailService,
		private OptionService $optionService,
		private PollService $pollService,
		private ShareService $shareService,
		private SubscriptionService $subscriptionService,
		private SystemService $systemService,
		private VoteService $voteService,
		private WatchService $watchService
	) {
		parent::__construct($appName, $request, $session);
	}

	/**
	 * @return TemplateResponse|PublicTemplateResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function votePage(string $token) {
		Util::addScript(AppConstants::APP_ID, 'polls-main');
		if ($this->userSession->isLoggedIn()) {
			return new TemplateResponse(AppConstants::APP_ID, 'main');
		} else {
			$template = new PublicTemplateResponse(AppConstants::APP_ID, 'main');
			$template->setFooterVisible(false);
			return $template;
		}
	}

	/**
	 * get complete poll via token
	 */
	#[PublicPage]
	public function getPoll(string $token): JSONResponse {
		$this->acl->setToken($token);

		return $this->response(fn () => [
			'acl' => $this->acl,
			'poll' => $this->pollService->get($this->acl->getPollId()),
		], $token);
	}

	/**
	 * Watch poll for updates
	 */
	#[PublicPage]
	public function watchPoll(string $token, ?int $offset): JSONResponse {
		return $this->responseLong(fn () => [
			'updates' => $this->watchService->watchUpdates($this->acl->setToken($token)->getPollId(), $offset)
		], $token);
	}

	/**
	 * Get share
	 */
	#[PublicPage]
	public function getShare(string $token): JSONResponse {
		$validateShareType = true;
		$publicRequest = true;
		return $this->response(fn () => [
			'share' => $this->shareService->get($token, $validateShareType, $publicRequest)
		], $token);
	}

	/**
	 * Get votes
	 */
	#[PublicPage]
	public function getVotes(string $token): JSONResponse {
		$this->acl->setToken($token);
		
		return $this->response(fn () => [
			'votes' => $this->voteService->list(acl: $this->acl)
		], $token);
	}

	/**
	 * Delete user's votes
	 */
	#[PublicPage]
	public function deleteUser(string $token): JSONResponse {
		return $this->response(fn () => [
			'deleted' => $this->voteService->delete(acl: $this->acl->setToken($token, Acl::PERMISSION_VOTE_EDIT))
		], $token);
	}

	/**
	 * Get options
	 */
	#[PublicPage]
	public function getOptions(string $token): JSONResponse {
		return $this->response(fn () => [
			'options' => $this->optionService->list(acl: $this->acl->setToken($token))
		], $token);
	}

	/**
	 * Add options
	 */
	#[PublicPage]
	public function addOption(string $token, int $timestamp = 0, string $text = '', int $duration = 0): JSONResponse {
		return $this->responseCreate(fn () => [
			'option' => $this->optionService->add(
				timestamp: $timestamp,
				pollOptionText: $text,
				duration: $duration,
				acl: $this->acl->setToken($token, Acl::PERMISSION_OPTIONS_ADD)
			)
		], $token);
	}

	/**
	 * Delete option
	 */
	#[PublicPage]
	public function deleteOption(string $token, int $optionId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => [
			'option' => $this->optionService->delete($optionId, $this->acl->setToken($token, Acl::PERMISSION_POLL_VIEW))
		], $token);
	}

	/**
	 * Set Vote
	 */
	#[PublicPage]
	public function setVote(int $optionId, string $setTo, string $token): JSONResponse {
		return $this->response(fn () => [
			'vote' => $this->voteService->set($optionId, $setTo, $this->acl->setToken($token, Acl::PERMISSION_VOTE_EDIT))
		], $token);
	}

	/**
	 * Get Comments
	 */
	#[PublicPage]
	public function getComments(string $token): JSONResponse {
		return $this->response(fn () => [
			'comments' => $this->commentService->list($this->acl->setToken($token))
		], $token);
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 */
	#[PublicPage]
	public function addComment(string $token, string $message): JSONResponse {
		return $this->response(fn () => [
			'comment' => $this->commentService->add($message, $this->acl->setToken($token, Acl::PERMISSION_COMMENT_ADD))
		], $token);
	}

	/**
	 * Delete Comment
	 */
	#[PublicPage]
	public function deleteComment(int $commentId, string $token): JSONResponse {
		$comment = $this->commentService->get($commentId);
		return $this->responseDeleteTolerant(fn () => [
			'comment' => $this->commentService->delete($comment, $this->acl->setToken($token, Acl::PERMISSION_COMMENT_ADD))
		], $token);
	}

	/**
	 * Restore deleted Comment
	 */
	#[PublicPage]
	public function restoreComment(int $commentId, string $token): JSONResponse {
		$comment = $this->commentService->get($commentId);

		return $this->response(fn () => [
			'comment' => $this->commentService->delete($comment, $this->acl->setToken($token, Acl::PERMISSION_COMMENT_ADD), true)
		]);
	}

	/**
	 * Get subscription status
	 */
	#[PublicPage]
	public function getSubscription(string $token): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->get($this->acl->setToken($token))
		], $token);
	}

	/**
	 * subscribe
	 */
	#[PublicPage]
	public function subscribe(string $token): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(true, $this->acl->setToken($token))
		], $token);
	}

	/**
	 * Unsubscribe
	 */
	#[PublicPage]
	public function unsubscribe(string $token): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(false, $this->acl->setToken($token))
		], $token);
	}

	/**
	 * Validate it the user name is reserved
	 * return false, if this username already exists as a user or as
	 * a participant of the poll
	 */
	#[PublicPage]
	public function validatePublicUsername(string $userName, string $token): JSONResponse {
		return $this->response(fn () => [
			'result' => $this->systemService->validatePublicUsername($userName, $this->shareService->get($token)), 'name' => $userName
		], $token);
	}

	/**
	 * Validate email address (simple validation)
	 */
	#[PublicPage]
	public function validateEmailAddress(string $emailAddress): JSONResponse {
		return $this->response(fn () => [
			'result' => $this->systemService->validateEmailAddress($emailAddress), 'emailAddress' => $emailAddress
		]);
	}

	/**
	 * Change displayName
	 */
	#[PublicPage]
	public function setDisplayName(string $token, string $displayName): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setDisplayname($this->shareService->get($token), $displayName)
		], $token);
	}


	/**
	 * Set EmailAddress
	 */
	#[PublicPage]
	public function setEmailAddress(string $token, string $emailAddress = ''): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setEmailAddress($this->shareService->get($token), $emailAddress, true)
		], $token);
	}

	/**
	 * Set EmailAddress
	 */
	#[PublicPage]
	public function deleteEmailAddress(string $token): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->deleteEmailAddress($this->shareService->get($token))
		], $token);
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 */
	#[PublicPage]
	public function register(string $token, string $userName, string $emailAddress = '', string $timeZone = ''): JSONResponse {
		$publicShare = $this->shareService->get($token);
		$personalShare = $this->shareService->register($publicShare, $userName, $emailAddress, $timeZone);
		return $this->responseCreate(fn () => [
			'share' => $personalShare,
		], $token);
	}

	/**
	 * Sent invitation mails for a share
	 * Additionally send notification via notifications
	 */
	#[PublicPage]
	public function resendInvitation(string $token): JSONResponse {
		$share = $this->shareService->get($token);
		return $this->response(fn () => [
			'share' => $share,
			'sentResult' => $this->mailService->sendInvitation($share)
		], $token);
	}
}
