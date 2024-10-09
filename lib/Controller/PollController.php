<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Model\Acl as Acl;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\MailService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SubscriptionService;
use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Server;

/**
 * @psalm-api
 */
class PollController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private Acl $acl,
		private MailService $mailService,
		private OptionService $optionService,
		private PollService $pollService,
		private VoteService $voteService,
		private CommentService $commentService,
		private SubscriptionService $subscriptionService,
		private ShareService $shareService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get list of polls
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/polls')]
	public function list(): JSONResponse {
		return $this->response(function () {
			$appSettings = Server::get(AppSettings::class);
			return [
				'list' => $this->pollService->list(),
				'permissions' => [
					'pollCreationAllowed' => $appSettings->getPollCreationAllowed(),
					'comboAllowed' => $appSettings->getComboAllowed(),
				],
			];
		});
	}

	/**
	 * get poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/poll/{pollId}/poll')]
	public function get(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->get($pollId),
			'acl' => $this->acl,
		]);
	}

	/**
	 * get complete poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/poll/{pollId}')]
	public function getFull(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->get($pollId),
			'options' => $this->optionService->list($pollId),
			'votes' => $this->voteService->list($pollId),
			'comments' => $this->commentService->list($pollId),
			'shares' => $this->shareService->list($pollId),
			'subscribed' => $this->subscriptionService->get($pollId),
			'acl' => $this->acl,
		]);
	}

	/**
	 * Add poll
	 * @param string $title Poll title
	 * @param string $type Poll type ('datePoll', 'textPoll')
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/poll/add')]
	public function add(string $type, string $title): JSONResponse {
		return $this->responseCreate(fn () => $this->pollService->add($type, $title));
	}

	/**
	 * Update poll configuration
	 * @param int $pollId Poll id
	 * @param array $poll poll config
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/poll/{pollId}')]
	public function update(int $pollId, array $poll): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->update($pollId, $poll),
			'acl' => $this->acl,
		]);
	}

	/**
	 * Send confirmation mails
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/poll/{pollId}/confirmation')]
	public function sendConfirmation(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'confirmations' => $this->mailService->sendConfirmations($pollId),
		]);
	}

	/**
	 * Switch archived status (move to archive polls)
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/poll/{pollId}/toggleArchive')]
	public function toggleArchive(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->toggleArchive($pollId));
	}

	/**
	 * Delete poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/poll/{pollId}')]
	public function delete(int $pollId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => $this->pollService->delete($pollId));
	}

	/**
	 * Close poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/poll/{pollId}/close')]
	public function close(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->close($pollId),
			'acl' => $this->acl,
		]);
	}

	/**
	 * Reopen poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/poll/{pollId}/reopen')]
	public function reopen(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->reopen($pollId),
			'acl' => $this->acl,
		]);
	}

	/**
	 * Clone poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/poll/{pollId}/clone')]
	public function clone(int $pollId): JSONResponse {
		return $this->response(fn () => $this->clonePoll($pollId));
	}

	private function clonePoll(int $pollId): JSONResponse {
		$poll = $this->pollService->clone($pollId);
		$this->optionService->clone($pollId, $poll->getId());
		return $this->get($pollId);
	}

	/**
	 * Transfer polls between users
	 * @param string $sourceUser User to transfer polls from
	 * @param string $targetUser User to transfer polls to
	 */
	#[FrontpageRoute(verb: 'PUT', url: '/poll/transfer/{sourceUser}/{targetUser}')]
	public function transferPolls(string $sourceUser, string $targetUser): JSONResponse {
		return $this->response(fn () => $this->pollService->transferPolls($sourceUser, $targetUser));
	}

	/**
	 * Collect email addresses from particitipants
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/poll/{pollId}/addresses')]
	public function getParticipantsEmailAddresses(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->getParticipantsEmailAddresses($pollId));
	}
}
