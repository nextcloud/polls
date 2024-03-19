<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 René Gieling <github@dartcafe.de>
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
use OCP\IUserSession;
use OCP\Util;

/**
 * Always use parent's classe response* methods to make sure, the token gets set correctly.
 * Requesting the token inside the controller is not possible, because the token is submitted
 * as a paramter and not known while contruction time
 * i.e. ACL requests are not valid before calling the response* method
 * @psalm-api
 */
class PublicController extends BasePublicController {
	public function __construct(
		string $appName,
		IRequest $request,
		ISession $session,
		Acl $acl,
		private IUserSession $userSession,
		private CommentService $commentService,
		private MailService $mailService,
		private OptionService $optionService,
		private ShareService $shareService,
		private SubscriptionService $subscriptionService,
		private SystemService $systemService,
		private VoteService $voteService,
		private WatchService $watchService
	) {
		parent::__construct($appName, $request, $session, $acl);
	}

	/**
	 * @return TemplateResponse|PublicTemplateResponse
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	public function votePage() {
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
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function getPoll(string $token): JSONResponse {
		return $this->response(function () {
			$this->acl->request(Acl::PERMISSION_POLL_VIEW);
			// load poll through acl
			return [
				'acl' => $this->acl,
				'poll' => $this->acl->getPoll(),
			];
		}, $token);
	}

	/**
	 * Watch poll for updates
	 * @param string $token Share token
	 * @param ?int $offset only watch changes after this timestamp
	 */
	#[PublicPage]
	public function watchPoll(string $token, ?int $offset): JSONResponse {
		return $this->responseLong(fn () => [
			'updates' => $this->watchService->watchUpdates(offset: $offset)
		], $token);
	}

	/**
	 * Get share
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function getShare(string $token): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->request($token)
		], $token);
	}

	/**
	 * Get votes
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function getVotes(string $token): JSONResponse {
		return $this->response(fn () => [
			'votes' => $this->voteService->list()
		], $token);
	}

	/**
	 * Delete current user's votes
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function deleteUser(string $token): JSONResponse {
		return $this->response(fn () => [
			'deleted' => $this->voteService->deleteCurrentUserFromPoll()
		], $token);
	}

	/**
	 * Delete current user's orphaned votes
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function deleteOrphanedVotes(string $token): JSONResponse {
		return $this->response(fn () => [
			'deleted' => $this->voteService->deleteCurrentUserFromPoll(deleteOnlyOrphaned: true)
		], $token);
	}

	/**
	 * Get options
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function getOptions(string $token): JSONResponse {
		return $this->response(fn () => [
			'options' => $this->optionService->list()
		], $token);
	}

	/**
	 * Add options
	 * @param string $token Share token
	 * @param int $timestamp timestamp for datepoll
	 * @param string $text Option text for text poll
	 * @param int duration duration of option
	 */
	#[PublicPage]
	public function addOption(string $token, int $timestamp = 0, string $text = '', int $duration = 0): JSONResponse {
		return $this->responseCreate(fn () => [
			'option' => $this->optionService->addForCurrentPoll(
				timestamp: $timestamp,
				pollOptionText: $text,
				duration: $duration,
			)
		], $token);
	}

	/**
	 * Delete option
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function deleteOption(string $token, int $optionId): JSONResponse {
		return $this->response(fn () => [
			'option' => $this->optionService->delete($optionId)
		], $token);
	}

	/**
	 * Restore option
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function restoreOption(string $token, int $optionId): JSONResponse {
		return $this->response(fn () => [
			'option' => $this->optionService->delete($optionId, true)
		], $token);
	}

	/**
	 * Set Vote
	 * @param int $optionId poll id
	 * @param string $setTo Answer string
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function setVote(int $optionId, string $setTo, string $token): JSONResponse {
		return $this->response(fn () => [
			'vote' => $this->voteService->set($optionId, $setTo)
		], $token);
	}

	/**
	 * Get Comments
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function getComments(string $token): JSONResponse {
		return $this->response(fn () => [
			'comments' => $this->commentService->list()
		], $token);
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @param string $token Share token
	 * @param string $message Comment text to add
	 */
	#[PublicPage]
	public function addComment(string $token, string $message): JSONResponse {
		return $this->response(fn () => [
			'comment' => $this->commentService->add($message)
		], $token);
	}

	/**
	 * Delete Comment
	 * @param string $token Share token
	 * @param int $commentId Id of comment to delete
	 */
	#[PublicPage]
	public function deleteComment(int $commentId, string $token): JSONResponse {
		$comment = $this->commentService->get($commentId);
		return $this->response(fn () => [
			'comment' => $this->commentService->delete($comment)
		], $token);
	}

	/**
	 * Restore deleted Comment
	 * @param string $token Share token
	 * @param int $commentId Id of comment to restore
	 */
	#[PublicPage]
	public function restoreComment(int $commentId, string $token): JSONResponse {
		$comment = $this->commentService->get($commentId);

		return $this->response(fn () => [
			'comment' => $this->commentService->delete($comment, true)
		], $token);
	}

	/**
	 * Get subscription status
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function getSubscription(string $token): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->get()
		], $token);
	}

	/**
	 * subscribe
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function subscribe(string $token): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(true)
		], $token);
	}

	/**
	 * Unsubscribe
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function unsubscribe(string $token): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(false)
		], $token);
	}

	/**
	 * Validate it the user name is reserved
	 * return false, if this username already exists as a user or as a participant of the poll
	 * @param string $displayName Name string to check for validation
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function validatePublicDisplayName(string $displayName, string $token): JSONResponse {
		return $this->response(fn () => [
			'name' => $this->systemService->validatePublicUsernameByToken($displayName, $token)
		], $token);
	}

	/**
	 * Validate email address (simple validation)
	 * @param string $emailAddress Email address string to check for validation
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function validateEmailAddress(string $emailAddress, string $token = ''): JSONResponse {
		return $this->response(fn () => [
			'result' => MailService::validateEmailAddress($emailAddress), 'emailAddress' => $emailAddress
		], $token);
	}

	/**
	 * Change displayName
	 * @param string $displayName New name
	 * @param string $token Share token
	 */
	#[PublicPage]
	public function setDisplayName(string $token, string $displayName): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setDisplayname($displayName, $token)
		], $token);
	}


	/**
	 * Set EmailAddress
	 * @param string $token Share token
	 * @param string $emailAddress New email address
	 */
	#[PublicPage]
	public function setEmailAddress(string $token, string $emailAddress = ''): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setEmailAddress($this->shareService->get($token), $emailAddress)
		], $token);
	}

	/**
	 * Set EmailAddress
	 * @param string $token Share token
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
	 * @param string $token Share token
	 * @param string $displayName Name
	 * @param string $emailAddress Email address
	 * @param string $timeZone timezone string
	 */
	#[PublicPage]
	public function register(string $token, string $displayName, string $emailAddress = '', string $timeZone = ''): JSONResponse {
		return $this->responseCreate(fn () => [
			'share' => $this->shareService->register($token, $displayName, $emailAddress, $timeZone),
		], $token);
	}

	/**
	 * Sent invitation mails for a share
	 * Additionally send notification via notifications
	 * @param string $token Share token
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
