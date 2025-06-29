<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\PollGroupService;
use OCA\Polls\Service\PollService;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class PollGroupController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private PollService $pollService,
		private PollGroupService $pollGroupService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get list of pollgroups
	 *
	 * psalm-return JSONResponse<array{pollGroups: array<int, PollGroup>}>
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/pollgroups')]
	public function list(): JSONResponse {
		return $this->response(function () {
			return [
				'pollGroups' => $this->pollGroupService->listPollGroups(),
			];
		});
	}

	/**
	 * Create a new pollgroup with its title and add a poll to it
	 *
	 * @param int $pollId Poll id to add to the new pollgroup
	 * @param string $newPollGroupName Name of the new pollgroup
	 *
	 * psalm-return JSONResponse<array{pollGroup: PollGroup, poll: Poll}>
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'POST', url: '/pollgroup/new/poll/{pollId}')]
	public function add(int $pollId, string $newPollGroupName = ''): JSONResponse {
		return $this->response(fn () => [
			'pollGroup' => $this->pollGroupService->addPollToPollGroup($pollId, newPollGroupName: $newPollGroupName),
			'poll' => $this->pollService->get($pollId),
		]);
	}

	/**
	 * Add poll to pollgroup
	 * @param int $pollId Poll id
	 * @param int $pollGroupId Poll group id
	 *
	 * psalm-return JSONResponse<array{pollGroup: PollGroup, poll: Poll}>
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/pollgroup/{pollGroupId}/poll/{pollId}')]
	public function addPoll(int $pollId, int $pollGroupId): JSONResponse {
		return $this->response(fn () => [
			'pollGroup' => $this->pollGroupService->addPollToPollGroup($pollId, $pollGroupId),
			'poll' => $this->pollService->get($pollId),
		]);
	}

	/**
	 * Update Pollgroup
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/pollgroup/{pollGroupId}/update')]
	public function update(
		int $pollGroupId,
		string $name,
		string $titleExt,
		string $description,
	): JSONResponse {
		return $this->response(fn () => [
			'pollGroup' => $this->pollGroupService->updatePollGroup($pollGroupId, $name, $titleExt, $description),
		]);
	}

	/**
	 * Remove poll from pollgroup
	 * @param int $pollId Poll id
	 * @param int $pollGroupId Poll group id
	 *
	 * psalm-return JSONResponse<array{pollGroup: PollGroup | null, poll: Poll}>
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/pollgroup/{pollGroupId}/poll/{pollId}')]
	public function removePoll(int $pollId, int $pollGroupId): JSONResponse {
		return $this->response(fn () => [
			'pollGroup' => $this->pollGroupService->removePollFromPollGroup($pollId, $pollGroupId),
			'poll' => $this->pollService->get($pollId),
		]);
	}
}
