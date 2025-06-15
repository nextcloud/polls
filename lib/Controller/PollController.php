<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Db\Poll;
use OCA\Polls\Helper\Container;
use OCA\Polls\Model\Settings\AppSettings;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\MailService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SubscriptionService;
use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class PollController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
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
			$appSettings = Container::queryClass(AppSettings::class);
			return [
				'list' => $this->pollService->list(),
				'permissions' => [
					'pollCreationAllowed' => $appSettings->getPollCreationAllowed(),
					'comboAllowed' => $appSettings->getComboAllowed(),
				],
				'groups' => $this->pollService->groups(),
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
		]);
	}

	/**
	 * Add poll
	 * @param string $title Poll title
	 * @param string $type Poll type ('datePoll', 'textPoll')
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/poll/add')]
	public function add(string $type, string $title, string $votingVariant = Poll::VARIANT_SIMPLE): JSONResponse {
		return $this->response(
			fn () => [
				'poll' => $this->pollService->add($type, $title, $votingVariant)
			],
			Http::STATUS_CREATED
		);
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
		]);
	}

	/**
	 * Lock Anonymous
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/poll/{pollId}/lockAnonymous')]
	public function lockAnonymous(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->lockAnonymous($pollId),
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
		return $this->response(fn () => [
			'poll' => $this->pollService->toggleArchive($pollId)
		]);
	}

	/**
	 * Delete poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/poll/{pollId}')]
	public function delete(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->delete($pollId)
		]);
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
		]);
	}

	/**
	 * Clone poll
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/poll/{pollId}/clone')]
	public function clone(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->clonePoll($pollId)
		]);
	}

	private function clonePoll(int $pollId): Poll {
		$poll = $this->pollService->clone($pollId);
		$this->optionService->clone($pollId, $poll->getId());
		return $this->pollService->get($pollId);
	}

	/**
	 * Transfer polls between users
	 * @param string $sourceUserId User id to transfer polls from
	 * @param string $targetUserId User id to transfer polls to
	 */
	#[FrontpageRoute(verb: 'PUT', url: '/poll/transfer/{sourceUserId}/{targetUserId}')]
	public function transferPolls(string $sourceUserId, string $targetUserId): JSONResponse {
		return $this->response(fn () => $this->pollService->transferPolls($sourceUserId, $targetUserId));
	}

	/**
	 * Transfer ownership of one poll
	 * @param int $pollId poll to transfer ownership
	 * @param string $targetUserId User to transfer polls to
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/poll/{pollId}/changeowner/{targetUserId}')]
	public function changeOwner(int $pollId, string $targetUserId): JSONResponse {
		return $this->response(fn () => $this->pollService->transferPoll($pollId, $targetUserId));
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
