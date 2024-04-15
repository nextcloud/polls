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

use OCA\Polls\Service\CommentService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * @psalm-api
 */
class CommentApiController extends BaseApiController {
	public function __construct(
		string $appName,
		IRequest $request,
		private CommentService $commentService
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Read all comments of a poll based on the poll id and return list as array
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => [
			'comments' => $this->commentService->list($pollId)
		]);
	}

	/**
	 * Add comment
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function add(int $pollId, string $comment): JSONResponse {
		return $this->response(fn () => [
			'comment' => $this->commentService->add($comment, $pollId)
		]);
	}

	/**
	 * Delete comment
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function delete(int $commentId): JSONResponse {
		$comment = $this->commentService->get($commentId);

		return $this->response(fn () => [
			'comment' => $this->commentService->delete($comment)]);
	}

	/**
	 * Restore comment
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function restore(int $commentId): JSONResponse {
		$comment = $this->commentService->get($commentId);

		return $this->response(fn () => [
			'comment' => $this->commentService->delete($comment, true)
		]);
	}
}
