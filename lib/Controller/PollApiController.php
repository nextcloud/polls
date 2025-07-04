<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Db\Poll;
use OCA\Polls\Service\CommentService;
use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\ShareService;
use OCA\Polls\Service\SubscriptionService;
use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 * @psalm-import-type PollsPoll from \OCA\Polls\ResponseDefinitions
 *  */
class PollApiController extends BaseApiV2Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private CommentService $commentService,
		private PollService $pollService,
		private OptionService $optionService,
		private ShareService $shareService,
		private SubscriptionService $subscriptionService,
		private VoteService $voteService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get list of polls
	 *
	 * psalm-return DataResponse<array{polls: PollsPoll[]}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/polls', requirements: ['apiVersion' => '(v2)'])]
	public function listPolls(): DataResponse {
		return $this->response(fn () => ['polls' => $this->pollService->listPolls()]);
	}

	/**
	 * get complete poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}', requirements: ['apiVersion' => '(v2)'])]
	public function get(int $pollId): DataResponse {
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
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/poll', requirements: ['apiVersion' => '(v2)'])]
	public function add(string $type, string $title, string $votingVariant = Poll::VARIANT_SIMPLE): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->add($type, $title, $votingVariant)], Http::STATUS_CREATED);
	}

	/**
	 * Update poll configuration
	 * @param int $pollId Poll id
	 * @param array $pollConfiguration poll config
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}', requirements: ['apiVersion' => '(v2)'])]
	public function update(int $pollId, array $pollConfiguration): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->update($pollId, $pollConfiguration)]);
	}

	/**
	 * Switch deleted status (move to deleted polls)
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}/archive/toggle', requirements: ['apiVersion' => '(v2)'])]
	public function toggleArchive(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->toggleArchive($pollId)]);
	}

	/**
	 * Close poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}/close', requirements: ['apiVersion' => '(v2)'])]
	public function close(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->close($pollId)]);
	}

	/**
	 * Reopen poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}/reopen', requirements: ['apiVersion' => '(v2)'])]
	public function reopen(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->reopen($pollId)]);
	}

	/**
	 * Delete poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1.0/poll/{pollId}', requirements: ['apiVersion' => '(v2)'])]
	public function delete(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->delete($pollId)]);
	}

	/**
	 * Clone poll
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/poll/{pollId}/clone', requirements: ['apiVersion' => '(v2)'])]
	public function clone(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->clone($pollId)], Http::STATUS_CREATED);
	}

	/**
	 * Transfer all polls from one user to another (change owner of poll)
	 * @param string $sourceUserId User id to transfer polls from
	 * @param string $targetUserId User id to transfer polls to
	 */
	#[CORS]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/transfer/{sourceUserId}/{targetUserId}', requirements: ['apiVersion' => '(v2)'])]
	public function transferPolls(string $sourceUserId, string $targetUserId): DataResponse {
		return $this->response(fn () => ['transferred' => $this->pollService->transferPolls($sourceUserId, $targetUserId)]);
	}

	/**
	 * Transfer single poll to another user (change owner of poll)
	 * @param int $pollId Poll to transfer
	 * @param string $targetUserId User id to transfer the poll to
	 */
	#[CORS]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}/transfer/{targetUserId}', requirements: ['apiVersion' => '(v2)'])]
	public function transferPoll(int $pollId, string $targetUserId): DataResponse {
		return $this->response(fn () => ['transferred' => $this->pollService->transferPoll($pollId, $targetUserId)]);
	}

	/**
	 * Collect email addresses from particitipants
	 * @param int $pollId Poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/addresses', requirements: ['apiVersion' => '(v2)'])]
	public function getParticipantsEmailAddresses(int $pollId): DataResponse {
		return $this->response(fn () => ['addresses' => $this->pollService->getParticipantsEmailAddresses($pollId)]);
	}

	/**
	 * Get valid values for configuration options
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/enum', requirements: ['apiVersion' => '(v2)'])]
	public function enum(): DataResponse {
		return $this->response(fn () => ['enum' => $this->pollService->getValidEnum()]);
	}
}
