<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\WatchService;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class WatchController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private WatchService $watchService,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Watch poll for updates
	 * @param int $pollId poll id of poll to wqatch
	 * @param int|null $offset only watch changes after this timestamp
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	#[FrontpageRoute(verb: 'GET', url: '/poll/{pollId}/watch')]
	public function watchPoll(int $pollId, ?int $offset): JSONResponse {
		return $this->response(fn () => ['updates' => $this->watchService->watchUpdates($pollId, $offset)]);
	}
}
