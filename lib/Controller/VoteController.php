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
		private VoteService $voteService
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * list votes per poll
	 * @param int $pollId poll id
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
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
	public function set(int $optionId, string $setTo): JSONResponse {
		return $this->response(fn () => ['vote' => $this->voteService->set($optionId, $setTo)]);
	}

	/**
	 * Remove user from poll
	 * @param int $pollId poll id
	 * @param string $userId User to remove
	 */
	#[NoAdminRequired]
	public function delete(int $pollId, string $userId = ''): JSONResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->deletUserFromPoll($pollId, $userId)]);
	}

	/**
	 * Delete orphaned votes
	 * @param int $pollId poll id
	 * @param string $userId User to delete orphan votes from
	 */
	#[NoAdminRequired]
	public function deleteOrphaned(int $pollId, string $userId = ''): JSONResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->deletUserFromPoll($pollId, $userId, deleteOnlyOrphaned: true)]);
	}
}
