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
 * @psalm-import-type PollsOption from \OCA\Polls\ResponseDefinitions
 * @psalm-import-type PollsVote from \OCA\Polls\ResponseDefinitions
 * @psalm-import-type PollsComment from \OCA\Polls\ResponseDefinitions
 * @psalm-import-type PollsShare from \OCA\Polls\ResponseDefinitions
 */
class PollApiController extends BaseApiV2OCSController {
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
	 * 200: Returns list of polls
	 * @return DataResponse<Http::STATUS_OK, array{polls: list<PollsPoll>}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/polls')]
	public function listPolls(): DataResponse {
		return $this->response(fn () => ['polls' => $this->pollService->listPolls()]);
	}

	/**
	 * Get complete poll with all related data
	 * 200: Returns complete poll data
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{poll: PollsPoll, options: list<PollsOption>, votes: list<PollsVote>, comments: list<PollsComment>, shares: list<PollsShare>, subscribed: bool}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}')]
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
	 * 201: Poll created
	 * @param string $type Poll type ('datePoll', 'textPoll')
	 * @param string $title Poll title
	 * @param string $votingVariant Voting variant
	 * @return DataResponse<Http::STATUS_CREATED, array{poll: PollsPoll}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/poll')]
	public function add(string $type, string $title, string $votingVariant = Poll::VARIANT_SIMPLE): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->add($type, $title, $votingVariant)], Http::STATUS_CREATED);
	}

	/**
	 * Update poll configuration
	 * 200: Poll updated
	 * @param int $pollId Poll id
	 * @param array<string, mixed> $pollConfiguration Poll configuration
	 * @return DataResponse<Http::STATUS_OK, array{poll: PollsPoll, diff: array<string, mixed>, changes: array<string, mixed>}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}')]
	public function update(int $pollId, array $pollConfiguration): DataResponse {
		return $this->response(fn () => $this->pollService->update($pollId, $pollConfiguration));
	}

	/**
	 * Toggle archived status of a poll
	 * 200: Archive status toggled
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{poll: PollsPoll}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}/archive/toggle')]
	public function toggleArchive(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->toggleArchive($pollId)]);
	}

	/**
	 * Close poll
	 * 200: Poll closed
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{poll: PollsPoll}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}/close')]
	public function close(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->close($pollId)]);
	}

	/**
	 * Reopen poll
	 * 200: Poll reopened
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{poll: PollsPoll}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}/reopen')]
	public function reopen(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->reopen($pollId)]);
	}

	/**
	 * Delete poll
	 * 200: Poll deleted
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{poll: PollsPoll}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1.0/poll/{pollId}')]
	public function delete(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->delete($pollId)]);
	}

	/**
	 * Clone poll
	 * 201: Poll cloned
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_CREATED, array{poll: PollsPoll}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1.0/poll/{pollId}/clone')]
	public function clone(int $pollId): DataResponse {
		return $this->response(fn () => ['poll' => $this->pollService->clone($pollId)], Http::STATUS_CREATED);
	}

	/**
	 * Transfer all polls from one user to another
	 * 200: Polls transferred
	 * @param string $sourceUserId User id to transfer polls from
	 * @param string $targetUserId User id to transfer polls to
	 * @return DataResponse<Http::STATUS_OK, array{transferred: list<PollsPoll>}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/transfer/{sourceUserId}/{targetUserId}')]
	public function transferPolls(string $sourceUserId, string $targetUserId): DataResponse {
		return $this->response(fn () => ['transferred' => $this->pollService->transferPolls($sourceUserId, $targetUserId)]);
	}

	/**
	 * Transfer single poll to another user
	 * 200: Poll transferred
	 * @param int $pollId Poll to transfer
	 * @param string $targetUserId User id to transfer the poll to
	 * @return DataResponse<Http::STATUS_OK, array{transferred: PollsPoll}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/poll/{pollId}/transfer/{targetUserId}')]
	public function transferPoll(int $pollId, string $targetUserId): DataResponse {
		return $this->response(fn () => ['transferred' => $this->pollService->transferPoll($pollId, $targetUserId)]);
	}

	/**
	 * Collect email addresses from participants
	 * 200: Returns participant email addresses
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{addresses: list<array{displayName: string, emailAddress: string, combined: string}>}, array{}>
	 * @psalm-suppress InvalidReturnType InvalidReturnStatement
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/addresses')]
	public function getParticipantsEmailAddresses(int $pollId): DataResponse {
		return $this->response(fn () => ['addresses' => $this->pollService->getParticipantsEmailAddresses($pollId)]);
	}

}
