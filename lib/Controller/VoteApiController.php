<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class VoteApiController extends BaseApiV2Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private VoteService $voteService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Read all votes of a poll based on the poll id and return list as array
	 * @param int $pollId poll id
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/{apiVersion}/poll/{pollId}/votes', requirements: ['apiVersion' => '(v2)'])]
	public function list(int $pollId): DataResponse {
		return $this->response(fn () => ['votes' => $this->voteService->list($pollId)]);
	}

	/**
	 * Set vote answer
	 * @param int $optionId poll id
	 * @param string $answer Answer string ('yes', 'no', 'maybe')
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'PUT', url: '/api/{apiVersion}/option/{optionId}/vote/{answer}', requirements: ['apiVersion' => '(v2)'])]
	public function set(int $optionId, string $answer): DataResponse {
		return $this->response(fn () => ['vote' => $this->voteService->set($optionId, $answer)]);
	}

	/**
	 * Remove user from poll
	 * @param int $pollId poll id
	 * @param string $userId User to remove
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/{apiVersion}/poll/{pollId}/user/{userId}', requirements: ['apiVersion' => '(v2)'])]
	public function delete(int $pollId, string $userId = ''): DataResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->deleteUserFromPoll($pollId, $userId)]);
	}

	/**
	 * Delete orphaned votes
	 * @param int $pollId poll id
	 * @param string $userId User to delete orphan votes from
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'DELETE', url: '/api/{apiVersion}/poll/{pollId}/votes/orphaned', requirements: ['apiVersion' => '(v2)'])]
	public function deleteOrphaned(int $pollId, string $userId = ''): DataResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->deleteUserFromPoll($pollId, $userId, deleteOnlyOrphaned: true)]);
	}
}
