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

use OCA\Polls\Exceptions\Exception;
use OCA\Polls\Service\VoteService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class VoteApiController extends BaseApiController {
	public function __construct(
		string $appName,
		IRequest $request,
		private VoteService $voteService
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
	public function list(int $pollId): JSONResponse {
		try {
			return new JSONResponse(['votes' => $this->voteService->list($pollId)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['error' => 'No votes'], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Set vote answer
	 * @param int $optionId poll id
	 * @param string $answer Answer string ('yes', 'no', 'maybe')
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function set(int $optionId, string $answer): JSONResponse {
		try {
			return new JSONResponse(['vote' => $this->voteService->set($optionId, $answer)], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['error' => 'Option or poll not found'], Http::STATUS_NOT_FOUND);
		} catch (Exception $e) {
			return new JSONResponse(['message' => $e->getMessage()], $e->getStatus());
		}
	}

	/**
	 * Remove user from poll
	 * @param int $pollId poll id
	 * @param string $userId User to remove
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function delete(int $pollId, string $userId = ''): JSONResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->deletUserFromPoll($pollId, $userId)]);
	}

	/**
	 * Delete orphaned votes
	 * @param int $pollId poll id
	 * @param string $userId User to delete orphan votes from
	 */
	#[CORS]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function deleteOrphaned(int $pollId, string $userId = ''): JSONResponse {
		return $this->response(fn () => ['deleted' => $this->voteService->deletUserFromPoll($pollId, $userId, deleteOnlyOrphaned: true)]);
	}
}
