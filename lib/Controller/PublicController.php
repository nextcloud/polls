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
use OCA\Polls\Attribute\ShareTokenRequired;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Model\Acl;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\MailService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SubscriptionService;
use OCA\Polls\Service\SystemService;
use OCA\Polls\Service\VoteService;
use OCA\Polls\Service\WatchService;
use OCA\Polls\UserSession;
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
		Acl $acl,
		private IURLGenerator $urlGenerator,
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
		parent::__construct($appName, $request, $acl);
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 * @return TemplateResponse|PublicTemplateResponse
	 */
	public function votePage(string $token) {
	#[ShareTokenRequired]
	public function votePage() {
		Util::addScript(AppConstants::APP_ID, 'polls-main');
		if ($this->userSession->getIsLoggedIn()) {
			return new TemplateResponse(AppConstants::APP_ID, 'main');
		} else {
			$template = new PublicTemplateResponse(AppConstants::APP_ID, 'main');
			$template->setFooterVisible(false);
			return $template;
		}
	}

	/**
	 * get complete poll via token
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function getPoll(): JSONResponse {
		return $this->response(function () {
			$this->acl->request(Acl::PERMISSION_POLL_VIEW);
			// load poll through acl
			return [
				'acl' => $this->acl,
				'poll' => $this->acl->getPoll(),
			];
		});
	}

	/**
	 * Watch poll for updates
	 * @PublicPage
	 * @param ?int $offset only watch changes after this timestamp
	 */
	#[ShareTokenRequired]
	public function watchPoll(?int $offset): JSONResponse {
		return $this->responseLong(fn () => [
			'updates' => $this->watchService->watchUpdates(offset: $offset)
		]);
	}

	/**
	 * Get share
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function getShare(string $token): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->request($token)
		]);
	}

	/**
	 * Get votes
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function getVotes(): JSONResponse {
		return $this->response(fn () => [
			'votes' => $this->voteService->list()
		]);
	}

	/**
	 * Delete current user's votes
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function deleteUser(): JSONResponse {
		return $this->response(fn () => [
			'deleted' => $this->voteService->deleteCurrentUserFromPoll()
		]);
	}

	/**
	 * Delete current user's orphaned votes
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function deleteOrphanedVotes(): JSONResponse {
		return $this->response(fn () => [
			'deleted' => $this->voteService->deleteCurrentUserFromPoll(deleteOnlyOrphaned: true)
		]);
	}

	/**
	 * Get options
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function getOptions(): JSONResponse {
		return $this->response(fn () => [
			'options' => $this->optionService->list()
		]);
	}

	/**
	 * Add options
	 * @PublicPage
	 * @param int $timestamp timestamp for datepoll
	 * @param string $text Option text for text poll
	 * @param int duration duration of option
	 */
	#[ShareTokenRequired]
	public function addOption(int $timestamp = 0, string $text = '', int $duration = 0): JSONResponse {
		return $this->responseCreate(fn () => [
			'option' => $this->optionService->add(
				timestamp: $timestamp,
				pollOptionText: $text,
				duration: $duration,
			)
		]);
	}

	/**
	 * Delete option
	 * @PublicPage
	 * @param int $optionId Option Id to delete
	 */
	#[ShareTokenRequired]
	public function deleteOption(int $optionId): JSONResponse {
		return $this->response(fn () => [
			'option' => $this->optionService->delete($optionId)
		]);
	}

	/**
	 * Restore option
	 * @PublicPage
	 * @param int $optionId Option Id to restore
	 */
	#[ShareTokenRequired]
	public function restoreOption(int $optionId): JSONResponse {
		return $this->response(fn () => [
			'option' => $this->optionService->delete($optionId, true)
		]);
	}

	/**
	 * Set Vote
	 * @PublicPage
	 * @param int $optionId poll id
	 * @param string $setTo Answer string
	 */
	#[ShareTokenRequired]
	public function setVote(int $optionId, string $setTo): JSONResponse {
		return $this->response(fn () => [
			'vote' => $this->voteService->set($optionId, $setTo)
		]);
	}

	/**
	 * Get Comments
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function getComments(): JSONResponse {
		return $this->response(fn () => [
			'comments' => $this->commentService->list()
		]);
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @PublicPage
	 * @param string $message Comment text to add
	 */
	#[ShareTokenRequired]
	public function addComment(string $message): JSONResponse {
		return $this->response(fn () => [
			'comment' => $this->commentService->add($message)
		]);
	}

	/**
	 * Delete Comment
	 * @PublicPage
	 * @param int $commentId Id of comment to delete
	 */
	#[ShareTokenRequired]
	public function deleteComment(int $commentId): JSONResponse {
		$comment = $this->commentService->get($commentId);
		return $this->response(fn () => [
			'comment' => $this->commentService->delete($comment)
		]);
	}

	/**
	 * Restore deleted Comment
	 * @PublicPage
	 * @param int $commentId Id of comment to restore
	 */
	#[ShareTokenRequired]
	public function restoreComment(int $commentId): JSONResponse {
		$comment = $this->commentService->get($commentId);

		return $this->response(fn () => [
			'comment' => $this->commentService->delete($comment, true)
		]);
	}

	/**
	 * Get subscription status
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function getSubscription(): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->get()
		]);
	}

	/**
	 * subscribe
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function subscribe(): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(true)
		]);
	}

	/**
	 * Unsubscribe
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function unsubscribe(): JSONResponse {
		return $this->response(fn () => [
			'subscribed' => $this->subscriptionService->set(false)
		]);
	}

	/**
	 * Validate it the user name is reserved
	 * return false, if this username already exists as a user or as a participant of the poll
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function validatePublicDisplayName(string $displayName, string $token): JSONResponse {
		return $this->response(fn () => [
			'name' => $this->systemService->validatePublicUsernameByToken($displayName, $token)
		]);
	}

	/**
	 * Validate email address (simple validation)
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function validateEmailAddress(string $emailAddress): JSONResponse {
		return $this->response(fn () => [
			'result' => MailService::validateEmailAddress($emailAddress), 'emailAddress' => $emailAddress
		]);
	}

	/**
	 * Change displayName
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function setDisplayName(string $token, string $displayName): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setDisplayname($displayName, $token)
		]);
	}


	/**
	 * Set EmailAddress
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function setEmailAddress(string $token, string $emailAddress = ''): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->setEmailAddress($this->shareService->get($token), $emailAddress)
		]);
	}

	/**
	 * Set EmailAddress
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function deleteEmailAddress(string $token): JSONResponse {
		return $this->response(fn () => [
			'share' => $this->shareService->deleteEmailAddress($this->shareService->get($token))
		]);
	}

	/**
	 * Create a personal share from a public share
	 * or update an email share with the username
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function register(string $token, string $displayName, string $emailAddress = '', string $timeZone = ''): JSONResponse {
		return $this->responseCreate(fn () => [
			'share' => $this->shareService->register($token, $displayName, $emailAddress, $timeZone),
		]);
	}

	/**
	 * Sent invitation mails for a share
	 * Additionally send notification via notifications
	 * @PublicPage
	 */
	#[ShareTokenRequired]
	public function resendInvitation(string $token): JSONResponse {
		$share = $this->shareService->get($token);
		return $this->response(fn () => [
			'share' => $share,
			'sentResult' => $this->mailService->sendInvitation($share)
		]);
	}
}
