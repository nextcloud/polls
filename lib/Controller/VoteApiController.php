<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

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
 * @psalm-import-type PollsVote from \OCA\Polls\ResponseDefinitions
 */
class VoteApiController extends BaseApiV2OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private VoteService $voteService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get all votes of a poll
	 * 200: Returns list of votes
	 * @param int $pollId Poll id
	 * @return DataResponse<Http::STATUS_OK, array{votes: list<PollsVote>}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/votes')]
	public function list(int $pollId): DataResponse {
		return $this->response(fn () => ['votes' => array_values(array_map(fn ($v) => $v->jsonSerialize(), $this->voteService->list($pollId)))]);
	}

	/**
	 * Set vote answer
	 * 200: Vote answer set
	 * @param int $optionId Option id
	 * @param string $answer Answer string ('yes', 'no', 'maybe')
	 * @return DataResponse<Http::STATUS_OK, array{vote: PollsVote|null}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/v1.0/option/{optionId}/vote/{answer}')]
	public function set(int $optionId, string $answer): DataResponse {
		return $this->response(fn () => ['vote' => $this->voteService->set($optionId, $answer)?->jsonSerialize()]);
	}

	/**
	 * Remove user from poll
	 * 200: User removed from poll
	 * @param int $pollId Poll id
	 * @param string $userId User to remove
	 * @return DataResponse<Http::STATUS_OK, array{deleted: string}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1.0/poll/{pollId}/user/{userId}')]
	public function delete(int $pollId, string $userId = ''): DataResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->deleteUserFromPoll($pollId, $userId)]);
	}

	/**
	 * Delete orphaned votes of a user
	 * 200: Orphaned votes deleted
	 * @param int $pollId Poll id
	 * @param string $userId User to delete orphan votes from
	 * @return DataResponse<Http::STATUS_OK, array{deleted: string}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/v1.0/poll/{pollId}/votes/orphaned')]
	public function deleteOrphaned(int $pollId, string $userId = ''): DataResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->deleteUserFromPoll($pollId, $userId, deleteOnlyOrphaned: true)]);
	}
}
