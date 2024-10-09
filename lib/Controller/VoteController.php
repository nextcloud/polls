<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\OptionService;
use OCA\Polls\Service\PollService;
use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class VoteController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private VoteService $voteService,
		private PollService $pollService,
		private OptionService $optionService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * list votes per poll
	 * @param int $pollId poll id
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/poll/{pollId}/votes')]
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => ['votes' => $this->voteService->list($pollId)]);
	}

	/**
	 * set vote answer
	 * @param int $optionId poll id
	 * @param string $setTo Answer string
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/vote')]
	// #[FrontpageRoute(verb: 'PUT', url: '/vote/{optionId}/set/{setTo}')]
	public function set(int $optionId, string $setTo): JSONResponse {
		$option = $this->optionService->get($optionId);
		return $this->response(fn () => [
			'vote' => $this->voteService->set($optionId, $setTo),
			'poll' => $this->pollService->get($option->getPollId()),
			'options' => $this->optionService->list($option->getPollId()),
		
		]);
	}

	/**
	 * Remove user from poll
	 * @param int $pollId poll id
	 * @param string $userId User to remove
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/poll/{pollId}/user/{userId}', postfix: 'named')]
	#[FrontpageRoute(verb: 'DELETE', url: '/poll/{pollId}/user', postfix: 'self')]
	public function delete(int $pollId, string $userId = ''): JSONResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->deleteUserFromPoll($pollId, $userId)]);
	}

	/**
	 * Delete orphaned votes
	 * @param int $pollId poll id
	 * @param string $userId User to delete orphan votes from
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/poll/{pollId}/votes/orphaned')]
	public function deleteOrphaned(int $pollId, string $userId = ''): JSONResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->deleteUserFromPoll($pollId, $userId, deleteOnlyOrphaned: true)]);
	}
}
