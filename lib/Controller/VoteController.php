<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Controller;

use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class VoteController extends BaseController {
	public function __construct(
		string $appName,
		IRequest $request,
		private VoteService $voteService
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * set
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => ['votes' => $this->voteService->list($pollId)]);
	}

	/**
	 * set
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function set(int $optionId, string $setTo): JSONResponse {
		return $this->response(fn () => ['vote' => $this->voteService->set($optionId, $setTo)]);
	}

	/**
	 * Remove user from poll
	 * @NoAdminRequired
	 */
	public function delete(int $pollId, string $userId = ''): JSONResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->delete($pollId, $userId)]);
	}
	/**
	 * Relete orphaned votes
	 * @NoAdminRequired
	 */
	public function deleteOrphaned(int $pollId, string $userId = ''): JSONResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->delete($pollId, $userId, true)]);
	}
}
