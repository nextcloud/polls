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
use OCA\Polls\Service\PollGroupService;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SubscriptionService;
use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
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
		private PollGroupService $pollGroupService,
		private VoteService $voteService,
		private CommentService $commentService,
		private SubscriptionService $subscriptionService,
		private ShareService $shareService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get list of polls
	 * psalm-return JSONResponse<array{
	 * 	polls: array<int, Poll>,
	 * 		permissions: array{
	 * 			pollCreationAllowed: bool,
	 * 			comboAllowed: bool
	 * 		},
	 * 	pollGroups: array<int, PollGroup>
	 * }>
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/polls')]
	public function listPolls(): JSONResponse {
		return $this->response(function () {
			$appSettings = Container::queryClass(AppSettings::class);
			return [
				'polls' => $this->pollService->listPolls(),
				'permissions' => [
					'pollCreationAllowed' => $appSettings->getPollCreationAllowed(),
					'comboAllowed' => $appSettings->getComboAllowed(),
				],
				'pollGroups' => $this->pollGroupService->listPollGroups(),
			];
		});
	}

	/**
	 * get poll
	 * @param int $pollId Poll id
	 *
	 * psalm-return JSONResponse<array{poll: Poll}>
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/poll/{pollId}/poll')]
	public function get(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'poll' => $this->pollService->get($pollId),
		]);
	}

	/**
	 * get complete poll
	 * @param int $pollId Poll id
	 *
	 * psalm-return JSONResponse<array{
	 * 	poll: Poll,
	 * 	options: array<int, Option>,
	 * 	votes: array<int, Vote>,
	 *  comments: array<int, Comment>,
	 *  shares: array<int, Share>,
	 *  subscribed: Subscription|null
	 * }>
	 *
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/poll/{pollId}')]
	public function getFull(int $pollId): JSONResponse {
		return $this->response(fn () => $this->getFullPoll($pollId, true), Http::STATUS_OK);
	}

	private function getFullPoll(int $pollId, bool $withTimings = false): array {
		$timerMicro['start'] = microtime(true);

		$poll = $this->pollService->get($pollId);
		$timerMicro['poll'] = microtime(true);

		$options = $this->optionService->list($pollId);
		$timerMicro['options'] = microtime(true);

		$votes = $this->voteService->list($pollId);
		$timerMicro['votes'] = microtime(true);

		$comments = $this->commentService->list($pollId);
		$timerMicro['comments'] = microtime(true);

		$shares = $this->shareService->list($pollId);
		$timerMicro['shares'] = microtime(true);

		$subscribed = $this->subscriptionService->get($pollId);
		$timerMicro['subscribed'] = microtime(true);

		$diffMicro['total'] = microtime(true) - $timerMicro['start'];
		$diffMicro['poll'] = $timerMicro['poll'] - $timerMicro['start'];
		$diffMicro['options'] = $timerMicro['options'] - $timerMicro['poll'];
		$diffMicro['votes'] = $timerMicro['votes'] - $timerMicro['options'];
		$diffMicro['comments'] = $timerMicro['comments'] - $timerMicro['votes'];
		$diffMicro['shares'] = $timerMicro['shares'] - $timerMicro['comments'];
		$diffMicro['subscribed'] = $timerMicro['subscribed'] - $timerMicro['shares'];

		if ($withTimings) {
			return [
				'poll' => $poll,
				'options' => $options,
				'votes' => $votes,
				'comments' => $comments,
				'shares' => $shares,
				'subscribed' => $subscribed,
				'diffMicro' => $diffMicro,
			];
		}
		return [
			'poll' => $poll,
			'options' => $options,
			'votes' => $votes,
			'comments' => $comments,
			'shares' => $shares,
			'subscribed' => $subscribed,
		];
	}

	/**
	 * Add poll
	 * @param string $title Poll title
	 * @param string $type Poll type ('datePoll', 'textPoll')
	 * @param string $votingVariant Voting variant (default: Poll::VARIANT_SIMPLE)
	 *
	 * psalm-return JSONResponse<array{poll: Poll}>
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
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
	 *
	 * psalm-return JSONResponse<array{poll: Poll}>
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'PUT', url: '/poll/{pollId}')]
	public function update(int $pollId, array $poll): JSONResponse {
		return $this->response(fn () => $this->pollService->update($pollId, $poll));
	}

	/**
	 * Lock Anonymous
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
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
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'PUT', url: '/poll/{pollId}/changeowner/{targetUserId}')]
	public function changeOwner(int $pollId, string $targetUserId): JSONResponse {
		return $this->response(fn () => $this->pollService->transferPoll($pollId, $targetUserId));
	}

	/**
	 * Collect email addresses from particitipants
	 * @param int $pollId Poll id
	 */
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/poll/{pollId}/addresses')]
	public function getParticipantsEmailAddresses(int $pollId): JSONResponse {
		return $this->response(fn () => $this->pollService->getParticipantsEmailAddresses($pollId));
	}

}
