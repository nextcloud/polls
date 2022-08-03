<?php
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
use OCP\ISession;

class CommentController extends BaseController {

	/** @var CommentService */
	private $commentService;

	public function __construct(
		string $appName,
		ISession $session,
		IRequest $request,
		CommentService $commentService
	) {
		parent::__construct($appName, $request, $session);
		$this->commentService = $commentService;
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 */
	public function list(int $pollId): JSONResponse {
		return $this->response(fn () => ['comments' => $this->commentService->list($pollId)]);
	}

	/**
	 * Write a new comment to the db and returns the new comment as array
	 * @NoAdminRequired
	 */
	public function add(int $pollId, string $message): JSONResponse {
		return $this->response(fn () => ['comment' => $this->commentService->add($message, $pollId)]);
	}

	/**
	 * Delete Comment
	 * @NoAdminRequired
	 */
	public function delete(int $commentId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => ['comment' => $this->commentService->delete($commentId)]);
	}
}
