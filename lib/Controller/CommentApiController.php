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

use OCA\Polls\Db\Comment;
use OCA\Polls\Model\Acl;
use OCA\Polls\Service\CommentService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class CommentApiController extends BaseApiController {
	public function __construct(
		string $appName,
		IRequest $request,
		private Acl $acl,
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
			'comments' => $this->commentService->list($this->acl->setPollId($pollId))
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
			'comment' => $this->commentService->add($comment, $this->acl->setPollId($pollId, Acl::PERMISSION_COMMENT_ADD))
		]);
	}

	/**
	 * Delete comment
	 * @NoAdminRequired
	 * @CORS
	 * @NoCSRFRequired
	 */
	public function delete(int $commentId): JSONResponse {
		return $this->responseDeleteTolerant(fn () => [
			'comment' => $this->deleteComment($commentId)
		]);
	}

	private function deleteComment(int $commentId): Comment {
		$comment = $this->commentService->get($commentId);
		return $this->commentService->delete($comment, $this->acl->setPollId($comment->getPollId()));
	}
}
