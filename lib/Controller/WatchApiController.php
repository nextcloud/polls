<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\WatchService;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class WatchApiController extends BaseApiV2Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private WatchService $watchService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Watch poll for updates
	 * @param int $pollId poll id of poll to wqtch
	 * @param int|null $offset only watch changes after this timestamp
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1.0/poll/{pollId}/watch', requirements: ['apiVersion' => '(v2)'])]
	public function watchPoll(int $pollId, ?int $offset): DataResponse {
		return $this->response(fn () => ['updates' => $this->watchService->watchUpdates($pollId, $offset)]);
	}
}
