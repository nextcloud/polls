<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\WatchService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 * @psalm-import-type PollsWatch from \OCA\Polls\ResponseDefinitions
 */
class WatchApiController extends BaseApiV2OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private WatchService $watchService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Watch poll for updates
	 * 200: Returns poll updates
	 * @param int $pollId Poll id of poll to watch
	 * @param string $mode The mode of watching, e.g. 'longPolling'
	 * @param int|null $offset Only watch changes after this timestamp
	 * @return DataResponse<Http::STATUS_OK, array{updates: list<PollsWatch>}, array{}>
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/watch')]
	public function watchPoll(int $pollId, string $mode, ?int $offset): DataResponse {
		return $this->response(fn () => ['updates' => array_map(fn ($w) => $w->jsonSerialize(), $this->watchService->watchUpdates($pollId, $mode, $offset))]);
	}
}
